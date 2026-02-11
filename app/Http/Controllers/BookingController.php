<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $company = $this->getCompany();

        if (! $company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        $bookings = Booking::where('company_id', $company->id)->get();

        return response()->json(['data' => $bookings]);
    }

    public function store(Request $request)
    {
        $company = $this->getCompany();

        if (! $company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_id'     => ['nullable', Rule::exists('customers', 'id')],
            'name'            => 'nullable|string|max:255',
            'email'           => 'nullable|email|max:255',
            'phone'           => 'nullable|string|max:50',
            'vehicle_id'      => ['nullable', Rule::exists('vehicles', 'id')],
            'driver_id'       => ['nullable', Rule::exists('drivers', 'id')],
            'service_type'    => ['required', Rule::in(['point_to_point', 'hourly', 'airport', 'custom'])],
            'pickup_address'  => 'required|string',
            'dropoff_address' => 'nullable|string',
            'pickup_time'     => 'required|date',
            'dropoff_time'    => 'nullable|date|after_or_equal:pickup_time',
            'passengers'      => 'required|integer|min:1',
            'child_seats'     => 'nullable|integer|min:0',
            'bags'            => 'nullable|integer|min:0',
            'flight_number'   => 'nullable|string|max:100',
            'airlines'        => 'nullable|string|max:100',
            'distance_km'     => [
                Rule::requiredIf(fn () => $request->service_type !== 'hourly'),
                'numeric',
                'min:0',
                'nullable',
            ],
            'hours'           => 'nullable|numeric|min:0',
            'base_price'      => 'required|numeric|min:0',
            'extras_price'    => 'nullable|numeric|min:0',
            'taxes'           => 'nullable|numeric|min:0',
            'gratuity'        => 'nullable|numeric|min:0',
            'parking'         => 'nullable|numeric|min:0',
            'others'          => 'nullable|numeric|min:0',
            'payment_method'  => 'nullable|string|max:100',
            'payment_status'  => 'nullable|string|max:100',
            'status'          => ['nullable', Rule::in(['pending', 'confirmed', 'assigned', 'on_route', 'completed', 'cancelled'])],
            'notes'           => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->service_type === 'hourly' && ! $request->filled('hours')) {
            return response()->json([
                'message' => 'Hours is required for hourly service'
            ], 422);
        }

        $authUser = $request->user();
        if ($authUser instanceof Customer) {
            if ($request->filled('customer_id') && (int) $request->customer_id !== (int) $authUser->id) {
                return response()->json([
                    'message' => 'Unauthorized customer_id'
                ], 403);
            }
        }

        $pickup = Carbon::parse($request->pickup_time);
        $dropoff = $request->filled('dropoff_time') ? Carbon::parse($request->dropoff_time) : null;

        $unavailableVehicleIds = $this->getUnavailableVehicleIds($company->id, $pickup, $dropoff);

        $vehicles = Vehicle::with('vehicleClass:id,name')
            ->whereNotIn('id', $unavailableVehicleIds)
            ->get();

        $passengers = (int) $request->passengers;
        $minCap = $passengers + 2;
        $maxCap = $passengers + 4;

        $vehicleOptions = $vehicles->map(function ($vehicle) use ($request, $minCap, $maxCap) {
            $priceCalculation = $this->calculatePrice($vehicle, $this->buildPriceInput($request));

            $capacity = (int) ($vehicle->capacity ?? 0);
            $recommended = $capacity >= $minCap && $capacity <= $maxCap;

            return [
                'vehicle_id' => $vehicle->id,
                'name' => $vehicle->name,
                'class' => $vehicle->vehicleClass?->name,
                'image' => $vehicle->image,
                'capacity' => $vehicle->capacity,
                'rate' => $priceCalculation['rate'],
                'base_price' => $priceCalculation['base_price'],
                'distance_km' => $priceCalculation['distance_km'],
                'hours' => $priceCalculation['hours'],
                'total_price' => $priceCalculation['total_price'],
                'recommended' => $recommended,
            ];
        })->values();

        if (! $request->filled('vehicle_id')) {
            return response()->json([
                'data' => [
                    'vehicle_options' => $vehicleOptions,
                ],
            ]);
        }

        if (in_array((int) $request->vehicle_id, $unavailableVehicleIds, true)) {
            return response()->json([
                'message' => 'Selected vehicle is not available for the requested time'
            ], 409);
        }

        $vehicle = $vehicles->firstWhere('id', (int) $request->vehicle_id) ?? Vehicle::find($request->vehicle_id);
        if (! $vehicle) {
            return response()->json([
                'message' => 'Vehicle not found'
            ], 404);
        }

        try {
            $booking = DB::transaction(function () use ($request, $company, $vehicle) {
                $priceCalculation = $this->calculatePrice($vehicle, $this->buildPriceInput($request));

                $data = $request->only([
                    'customer_id',
                    'name',
                    'email',
                    'phone',
                    'vehicle_id',
                    'driver_id',
                    'service_type',
                    'pickup_address',
                    'dropoff_address',
                    'pickup_time',
                    'dropoff_time',
                    'passengers',
                    'child_seats',
                    'bags',
                    'flight_number',
                    'airlines',
                    'distance_km',
                    'hours',
                    'base_price',
                    'extras_price',
                    'taxes',
                    'gratuity',
                    'parking',
                    'others',
                    'payment_method',
                    'payment_status',
                    'status',
                    'notes',
                ]);

                $data['company_id'] = $company->id;
                $data['total_price'] = $priceCalculation['total_price'];
                $data['final_price'] = $priceCalculation['total_price'];

                $authUser = $request->user();
                if ($authUser instanceof Customer) {
                    $data['customer_id'] = $authUser->id;
                    $data['name'] = $authUser->name;
                    $data['email'] = $authUser->email;
                    $data['phone'] = $authUser->phone;
                }

                return Booking::create($data);
            });

            return response()->json([
                'message' => 'Booking created successfully',
                'data' => $booking,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $company = $this->getCompany();

        if (! $company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        $booking = Booking::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (! $booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        return response()->json(['data' => $booking]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $company = $this->getCompany();

        if (! $company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        $booking = Booking::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (! $booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_id'     => ['sometimes', 'nullable', Rule::exists('customers', 'id')],
            'name'            => 'sometimes|nullable|string|max:255',
            'email'           => 'sometimes|nullable|email|max:255',
            'phone'           => 'sometimes|nullable|string|max:50',
            'vehicle_id'      => ['sometimes', Rule::exists('vehicles', 'id')],
            'driver_id'       => ['sometimes', 'nullable', Rule::exists('drivers', 'id')],
            'service_type'    => ['sometimes', Rule::in(['point_to_point', 'hourly', 'airport', 'custom'])],
            'pickup_address'  => 'sometimes|required|string',
            'dropoff_address' => 'sometimes|nullable|string',
            'pickup_time'     => 'sometimes|required|date',
            'dropoff_time'    => 'sometimes|nullable|date|after_or_equal:pickup_time',
            'passengers'      => 'sometimes|required|integer|min:1',
            'child_seats'     => 'sometimes|nullable|integer|min:0',
            'bags'            => 'sometimes|nullable|integer|min:0',
            'flight_number'   => 'sometimes|nullable|string|max:100',
            'airlines'        => 'sometimes|nullable|string|max:100',
            'distance_km'     => [
                'sometimes',
                Rule::requiredIf(fn () => $request->input('service_type', $booking->service_type) !== 'hourly'),
                'numeric',
                'min:0',
                'nullable',
            ],
            'hours'           => 'sometimes|nullable|numeric|min:0',
            'base_price'      => 'sometimes|required|numeric|min:0',
            'extras_price'    => 'sometimes|nullable|numeric|min:0',
            'taxes'           => 'sometimes|nullable|numeric|min:0',
            'gratuity'        => 'sometimes|nullable|numeric|min:0',
            'parking'         => 'sometimes|nullable|numeric|min:0',
            'others'          => 'sometimes|nullable|numeric|min:0',
            'payment_method'  => 'sometimes|nullable|string|max:100',
            'payment_status'  => 'sometimes|nullable|string|max:100',
            'status'          => ['sometimes', Rule::in(['pending', 'confirmed', 'assigned', 'on_route', 'completed', 'cancelled'])],
            'notes'           => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $newServiceType = $request->input('service_type', $booking->service_type);
        $newHours = $request->input('hours', $booking->hours);

        if ($newServiceType === 'hourly' && empty($newHours)) {
            return response()->json([
                'message' => 'Hours is required for hourly service'
            ], 422);
        }

        $authUser = $request->user();
        if ($authUser instanceof Customer) {
            if ($request->filled('customer_id') && (int) $request->customer_id !== (int) $authUser->id) {
                return response()->json([
                    'message' => 'Unauthorized customer_id'
                ], 403);
            }
        }

        // Check vehicle availability if time or vehicle is being changed
        $isTimeOrVehicleChanged = $request->hasAny(['pickup_time', 'dropoff_time', 'vehicle_id']);

        if ($isTimeOrVehicleChanged) {
            $newPickupTime = $request->input('pickup_time', $booking->pickup_time);
            $newDropoffTime = $request->input('dropoff_time', $booking->dropoff_time);
            $newVehicleId = $request->input('vehicle_id', $booking->vehicle_id);

            if (! empty($newVehicleId)) {
                $pickup = Carbon::parse($newPickupTime);
                $dropoff = $newDropoffTime ? Carbon::parse($newDropoffTime) : null;

                $unavailableVehicleIds = $this->getUnavailableVehicleIds($company->id, $pickup, $dropoff, $booking->id);

                if (in_array((int) $newVehicleId, $unavailableVehicleIds, true)) {
                    return response()->json([
                        'message' => 'Selected vehicle is not available for the requested time'
                    ], 409);
                }
            }
        }

        try {
            DB::transaction(function () use ($request, $booking) {
                $authUser = $request->user();

                // If authenticated user is a Customer, prevent overriding customer data
                if ($authUser instanceof Customer) {
                    $fillableData = $request->except(['customer_id', 'name', 'email', 'phone']);
                    $booking->fill($fillableData);
                } else {
                    $booking->fill($request->only([
                        'customer_id',
                        'name',
                        'email',
                        'phone',
                        'vehicle_id',
                        'driver_id',
                        'service_type',
                        'pickup_address',
                        'dropoff_address',
                        'pickup_time',
                        'dropoff_time',
                        'passengers',
                        'child_seats',
                        'bags',
                        'flight_number',
                        'airlines',
                        'distance_km',
                        'hours',
                        'base_price',
                        'extras_price',
                        'taxes',
                        'gratuity',
                        'parking',
                        'others',
                        'payment_method',
                        'payment_status',
                        'status',
                        'notes',
                    ]));
                }

                $recalc = $request->hasAny([
                    'vehicle_id',
                    'service_type',
                    'distance_km',
                    'hours',
                    'base_price',
                    'extras_price',
                    'taxes',
                    'gratuity',
                    'parking',
                    'others'
                ]);

                if ($recalc) {
                    $vehicle = Vehicle::find($booking->vehicle_id);
                    $priceCalculation = $this->calculatePrice($vehicle, $this->buildPriceInput($booking));

                    $booking->total_price = $priceCalculation['total_price'];
                    $booking->final_price = $priceCalculation['total_price'];
                }

                $booking->save();
            });

            return response()->json([
                'message' => 'Booking updated successfully',
                'data' => $booking->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $company = $this->getCompany();

        if (! $company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        $booking = Booking::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (! $booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $booking->delete();

        return response()->json(['message' => 'Booking deleted successfully'], 200);
    }

    /**
     * Get the company instance
     */
    private function getCompany(): ?Company
    {
        return Company::first();
    }

    /**
     * Get unavailable vehicle IDs for the given time range
     */
    private function getUnavailableVehicleIds(int $companyId, Carbon $pickup, ?Carbon $dropoff, ?int $excludeBookingId = null): array
    {
        $windowStart = $dropoff ? $pickup : $pickup->copy()->subHours(2);
        $windowEnd = $dropoff ? $dropoff : $pickup->copy()->addHours(2);

        $query = Booking::where('company_id', $companyId)
            ->where(function ($query) use ($windowStart, $windowEnd) {
                $query->where(function ($q) use ($windowStart, $windowEnd) {
                    $q->whereNotNull('dropoff_time')
                        ->where('pickup_time', '<=', $windowEnd)
                        ->where('dropoff_time', '>=', $windowStart);
                })->orWhere(function ($q) use ($windowStart, $windowEnd) {
                    $q->whereNull('dropoff_time')
                        ->whereBetween('pickup_time', [$windowStart, $windowEnd]);
                });
            });

        // Exclude the current booking when updating
        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return $query->pluck('vehicle_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Calculate price for a vehicle and booking data
     */
    private function calculatePrice(Vehicle $vehicle, array $data): array
    {
        $serviceType = $data['service_type'];
        $distanceKm = (float) $data['distance_km'];
        $hours = (float) $data['hours'];
        $basePrice = (float) $data['base_price'];
        $extrasPrice = (float) $data['extras_price'];
        $taxes = (float) $data['taxes'];
        $gratuity = (float) $data['gratuity'];
        $parking = (float) $data['parking'];
        $others = (float) $data['others'];

        $rate = 0;
        $units = 0;

        switch ($serviceType) {
            case 'hourly':
                $rate = (float) ($vehicle->hourly_rate ?? 0);
                $units = $hours;
                break;
            case 'airport':
                $rate = (float) ($vehicle->airport_rate ?? 0);
                $units = $distanceKm;
                break;
            case 'point_to_point':
            case 'custom':
            default:
                $rate = (float) ($vehicle->per_km_rate ?? 0);
                $units = $distanceKm;
                break;
        }

        $total = $basePrice + ($units * $rate) + $extrasPrice + $taxes + $gratuity + $parking + $others;

        return [
            'rate' => $rate,
            'units' => $units,
            'base_price' => $basePrice,
            'distance_km' => $distanceKm,
            'hours' => $hours,
            'extras_price' => $extrasPrice,
            'taxes' => $taxes,
            'gratuity' => $gratuity,
            'parking' => $parking,
            'others' => $others,
            'total_price' => $total,
        ];
    }

    private function buildPriceInput($data): array
    {
        return [
            'service_type' => $data->service_type,
            'distance_km' => (float) ($data->distance_km ?? 0),
            'hours' => (float) ($data->hours ?? 0),
            'base_price' => (float) ($data->base_price ?? 0),
            'extras_price' => (float) ($data->extras_price ?? 0),
            'taxes' => (float) ($data->taxes ?? 0),
            'gratuity' => (float) ($data->gratuity ?? 0),
            'parking' => (float) ($data->parking ?? 0),
            'others' => (float) ($data->others ?? 0),
        ];
    }
}
