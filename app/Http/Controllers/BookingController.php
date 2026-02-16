<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\SystemConfig;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

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

        $validator = Validator::make($request->all(), [
            'status' => ['sometimes', 'nullable', Rule::in(['pending', 'confirmed', 'assigned', 'on_route', 'completed', 'cancelled'])],
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Booking::where('company_id', $company->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->input('per_page', 15);
        $bookings = $query
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

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
            'extras_price'    => 'nullable|numeric|min:0',
            'parking'         => 'nullable|numeric|min:0',
            'others'          => 'nullable|numeric|min:0',
            'airport_fees'    => 'nullable|numeric|min:0',
            'congestion_charge' => 'nullable|numeric|min:0',
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

        $authUser = auth('sanctum')->user() ?? $request->user();
        if ($request->filled('customer_id')) {
            if ($authUser instanceof User) {
                if (! in_array((string) $authUser->user_type, ['admin', 'dispatcher'], true)) {
                    return response()->json([
                        'message' => 'Unauthorized customer_id'
                    ], 403);
                }
            } elseif ($authUser instanceof Customer) {
                if ((int) $request->customer_id !== (int) $authUser->id) {
                    return response()->json([
                        'message' => 'Unauthorized customer_id'
                    ], 403);
                }
            } else {
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

        $systemConfig = $this->getSystemConfig($company->id);

        $vehicleOptions = $vehicles->map(function ($vehicle) use ($request, $minCap, $maxCap, $systemConfig) {
            $priceCalculation = $this->calculatePrice($vehicle, $this->buildPriceInput($request, $systemConfig));

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
                'calculation' => $this->buildCalculationBreakdown($priceCalculation),
                'recommended' => $recommended,
            ];
        })->values();

        if (! $request->filled('vehicle_id')) {
            return response()->json([
                'data' => [
                    'service_type' => $request->service_type,
                    'passengers' => (int) $request->passengers,
                    'distance_km' => (float) ($request->distance_km ?? 0),
                    'hours' => (float) ($request->hours ?? 0),
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
            $booking = DB::transaction(function () use ($request, $company, $vehicle, $authUser) {
                $systemConfig = $this->getSystemConfig($company->id);
                $priceCalculation = $this->calculatePrice($vehicle, $this->buildPriceInput($request, $systemConfig));

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
                    'extras_price',
                    'parking',
                    'others',
                    'airport_fees',
                    'congestion_charge',
                    'payment_method',
                    'payment_status',
                    'status',
                    'notes',
                ]);

                $data['company_id'] = $company->id;
                $data['base_price'] = $priceCalculation['base_price'];
                $data['taxes'] = $priceCalculation['tax_rate'];
                $data['taxes_amount'] = $priceCalculation['taxes_amount'];
                $data['gratuity'] = $priceCalculation['gratuity_percentage'];
                $data['gratuity_amount'] = $priceCalculation['gratuity_amount'];
                $data['rate_buffer'] = $priceCalculation['rate_buffer'];
                $data['rate_buffer_amount'] = $priceCalculation['buffer_amount'];
                $data['surge_rate'] = $priceCalculation['surge_rate'];
                $data['surge_rate_amount'] = $priceCalculation['surge_rate_amount'];
                $data['cancellation_fee'] = $priceCalculation['cancellation_fee'];
                $data['total_price'] = $priceCalculation['total_price'];
                $data['final_price'] = $priceCalculation['total_price'];

                $isGuestBooking = ! ($authUser instanceof Customer) && ! ($authUser instanceof User);
                if ($isGuestBooking) {
                    $data['booking_access_token'] = Str::random(64);
                }

                if ($authUser instanceof Customer) {
                    $data['customer_id'] = $authUser->id;
                    $data['name'] = $authUser->name;
                    $data['email'] = $authUser->email;
                    $data['phone'] = $authUser->phone;
                } elseif (! empty($data['customer_id'])) {
                    $selectedCustomer = Customer::find($data['customer_id']);

                    if ($selectedCustomer) {
                        $data['name'] = $selectedCustomer->name;
                        $data['email'] = $selectedCustomer->email;
                        $data['phone'] = $selectedCustomer->phone;
                    }
                }

                $booking = Booking::create($data);
                $booking->setAttribute('price_calculation', $priceCalculation);
                if ($isGuestBooking) {
                    $booking->setAttribute('issued_booking_access_token', $data['booking_access_token']);
                }

                return $booking;
            });

            $freshBooking = $booking->fresh();
            $calc = $booking->getAttribute('price_calculation');
            $issuedBookingAccessToken = $booking->getAttribute('issued_booking_access_token');

            $response = [
                'message' => 'Booking created successfully',
                'data' => $freshBooking,
                'calculation' => $this->buildCalculationBreakdown($calc),
            ];

            if ($issuedBookingAccessToken) {
                $response['booking_access_token'] = $issuedBookingAccessToken;
            }

            return response()->json($response, 201);

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
            'extras_price'    => 'sometimes|nullable|numeric|min:0',
            'parking'         => 'sometimes|nullable|numeric|min:0',
            'others'          => 'sometimes|nullable|numeric|min:0',
            'airport_fees'    => 'sometimes|nullable|numeric|min:0',
            'congestion_charge' => 'sometimes|nullable|numeric|min:0',
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
                        'extras_price',
                        'parking',
                        'others',
                        'airport_fees',
                        'congestion_charge',
                        'payment_method',
                        'payment_status',
                        'status',
                        'notes',
                    ]));
                }

                $latestPriceCalculation = null;
                $isCancelled = (string) $booking->status === 'cancelled';

                if ($isCancelled) {
                    $systemConfig = $this->getSystemConfig($booking->company_id);
                    $latestPriceCalculation = $this->buildCancellationPriceCalculation(
                        cancellationFee: (float) ($systemConfig->cancellation_fee ?? 0),
                        serviceType: (string) ($booking->service_type ?? 'custom')
                    );

                    $booking->base_price = 0;
                    $booking->extras_price = 0;
                    $booking->parking = 0;
                    $booking->others = 0;
                    $booking->airport_fees = 0;
                    $booking->congestion_charge = 0;
                    $booking->taxes = 0;
                    $booking->taxes_amount = 0;
                    $booking->gratuity = 0;
                    $booking->gratuity_amount = 0;
                    $booking->rate_buffer = 0;
                    $booking->rate_buffer_amount = 0;
                    $booking->surge_rate = 0;
                    $booking->surge_rate_amount = 0;
                    $booking->cancellation_fee = $latestPriceCalculation['cancellation_fee'];
                    $booking->total_price = $latestPriceCalculation['total_price'];
                    $booking->final_price = $latestPriceCalculation['total_price'];
                }

                $recalc = ! $isCancelled && $request->hasAny([
                    'vehicle_id',
                    'service_type',
                    'distance_km',
                    'hours',
                    'extras_price',
                    'parking',
                    'others',
                    'airport_fees',
                    'congestion_charge',
                    'status',
                ]);

                if ($recalc) {
                    $vehicle = Vehicle::find($booking->vehicle_id);
                    $systemConfig = $this->getSystemConfig($booking->company_id);
                    $priceCalculation = $this->calculatePrice($vehicle, $this->buildPriceInput($booking, $systemConfig));

                    $booking->base_price = $priceCalculation['base_price'];
                    $booking->taxes = $priceCalculation['tax_rate'];
                    $booking->taxes_amount = $priceCalculation['taxes_amount'];
                    $booking->gratuity = $priceCalculation['gratuity_percentage'];
                    $booking->gratuity_amount = $priceCalculation['gratuity_amount'];
                    $booking->rate_buffer = $priceCalculation['rate_buffer'];
                    $booking->rate_buffer_amount = $priceCalculation['buffer_amount'];
                    $booking->surge_rate = $priceCalculation['surge_rate'];
                    $booking->surge_rate_amount = $priceCalculation['surge_rate_amount'];
                    $booking->cancellation_fee = $priceCalculation['cancellation_fee'];
                    $booking->total_price = $priceCalculation['total_price'];
                    $booking->final_price = $priceCalculation['total_price'];
                    $latestPriceCalculation = $priceCalculation;
                }

                $booking->save();

                // expose latest pricing flow after any update
                if (! $latestPriceCalculation) {
                    $vehicle = Vehicle::find($booking->vehicle_id);
                    $systemConfig = $this->getSystemConfig($booking->company_id);
                    $latestPriceCalculation = $this->calculatePrice($vehicle, $this->buildPriceInput($booking, $systemConfig));
                }

                $booking->setAttribute('price_calculation', $latestPriceCalculation);
            });

            $freshBooking = $booking->fresh();
            $calc = $booking->getAttribute('price_calculation');
            $cancellationPayment = null;

            if ((string) $freshBooking->status === 'cancelled') {
                $cancellationPayment = $this->captureCancellationPayment($freshBooking);
            }

            $response = [
                'message' => 'Booking updated successfully',
                'data' => $freshBooking,
                'calculation' => $this->buildCalculationBreakdown($calc),
            ];

            if ($cancellationPayment) {
                $response['cancellation_payment'] = $cancellationPayment;
            }

            return response()->json($response);

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

    private function getSystemConfig(int $companyId): ?SystemConfig
    {
        return SystemConfig::where('company_id', $companyId)->first();
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
        $taxRate = (float) $data['tax_rate'];
        $rateBuffer = (float) $data['rate_buffer'];
        $gratuityPercentage = (float) $data['gratuity_percentage'];
        $surgeRate = (float) $data['surge_rate'];
        $configuredCancellationFee = (float) $data['cancellation_fee'];
        $status = (string) ($data['status'] ?? '');
        $parking = (float) $data['parking'];
        $others = (float) $data['others'];
        $airportFees = (float) $data['airport_fees'];
        $congestionCharge = (float) $data['congestion_charge'];

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

        $cancellationFee = $status === 'cancelled' ? $configuredCancellationFee : 0;
        $subtotal = $basePrice + ($units * $rate) + $extrasPrice + $parking + $others + $airportFees + $congestionCharge;
        $surgeAmount = $subtotal * ($surgeRate / 100);
        $taxesAmount = ($subtotal + $surgeAmount) * ($taxRate / 100);
        $gratuityAmount = ($subtotal + $surgeAmount) * ($gratuityPercentage / 100);
        $preAuthBase = $subtotal + $surgeAmount + $taxesAmount + $gratuityAmount + $cancellationFee;
        $bufferAmount = $preAuthBase * ($rateBuffer / 100);
        $total = $preAuthBase + $bufferAmount;

        return [
            'service_type' => $serviceType,
            'rate' => $rate,
            'units' => $units,
            'base_price' => $basePrice,
            'distance_km' => $distanceKm,
            'hours' => $hours,
            'extras_price' => $extrasPrice,
            'tax_rate' => $taxRate,
            'rate_buffer' => $rateBuffer,
            'gratuity_percentage' => $gratuityPercentage,
            'surge_rate' => $surgeRate,
            'cancellation_fee' => $cancellationFee,
            'subtotal' => $subtotal,
            'surge_rate_amount' => $surgeAmount,
            'taxes_amount' => $taxesAmount,
            'gratuity_amount' => $gratuityAmount,
            'parking' => $parking,
            'others' => $others,
            'airport_fees' => $airportFees,
            'congestion_charge' => $congestionCharge,
            'buffer_amount' => $bufferAmount,
            'total_price' => $total,
        ];
    }

    private function buildPriceInput($data, ?SystemConfig $config = null): array
    {
        return [
            'service_type' => $data->service_type,
            'distance_km' => (float) ($data->distance_km ?? 0),
            'hours' => (float) ($data->hours ?? 0),
            'base_price' => (float) ($config->base_price_flat ?? 0),
            'extras_price' => (float) ($data->extras_price ?? 0),
            'tax_rate' => (float) ($config->tax_rate ?? 0),
            'rate_buffer' => (float) ($config->rate_buffer ?? 0),
            'gratuity_percentage' => (float) ($config->gratuity_percentage ?? 0),
            'surge_rate' => (float) ($config->surge_rate ?? 0),
            'cancellation_fee' => (float) ($config->cancellation_fee ?? 0),
            'status' => (string) ($data->status ?? ''),
            'parking' => (float) ($data->parking ?? 0),
            'others' => (float) ($data->others ?? 0),
            'airport_fees' => (float) ($data->airport_fees ?? 0),
            'congestion_charge' => (float) ($data->congestion_charge ?? 0),
        ];
    }

    private function buildCalculationBreakdown(?array $priceCalculation): ?array
    {
        if (! $priceCalculation) {
            return null;
        }

        $isHourly = ($priceCalculation['service_type'] ?? null) === 'hourly';
        $billedField = $isHourly ? 'hours' : 'km';
        $billedValue = $isHourly
            ? (float) ($priceCalculation['hours'] ?? 0)
            : (float) ($priceCalculation['distance_km'] ?? 0);

        return [
            'rate' => $priceCalculation['rate'],
            $billedField => $billedValue,
            'base_price' => $priceCalculation['base_price'],
            'extras_price' => $priceCalculation['extras_price'],
            'airport_fees' => $priceCalculation['airport_fees'],
            'congestion_charge' => $priceCalculation['congestion_charge'],
            'parking' => $priceCalculation['parking'],
            'others' => $priceCalculation['others'],
            'subtotal' => $priceCalculation['subtotal'],
            'surge_rate_percent' => $priceCalculation['surge_rate'],
            'surge_rate_amount' => $priceCalculation['surge_rate_amount'],
            'tax_rate_percent' => $priceCalculation['tax_rate'],
            'tax_amount' => $priceCalculation['taxes_amount'],
            'gratuity_percent' => $priceCalculation['gratuity_percentage'],
            'gratuity_amount' => $priceCalculation['gratuity_amount'],
            'rate_buffer_percent' => $priceCalculation['rate_buffer'],
            'rate_buffer_amount' => $priceCalculation['buffer_amount'],
            'cancellation_fee' => $priceCalculation['cancellation_fee'],
            'total_price' => $priceCalculation['total_price'],
        ];
    }

    private function buildCancellationPriceCalculation(float $cancellationFee, string $serviceType): array
    {
        return [
            'service_type' => $serviceType,
            'rate' => 0,
            'units' => 0,
            'base_price' => 0,
            'distance_km' => 0,
            'hours' => 0,
            'extras_price' => 0,
            'tax_rate' => 0,
            'rate_buffer' => 0,
            'gratuity_percentage' => 0,
            'surge_rate' => 0,
            'cancellation_fee' => $cancellationFee,
            'subtotal' => 0,
            'surge_rate_amount' => 0,
            'taxes_amount' => 0,
            'gratuity_amount' => 0,
            'parking' => 0,
            'others' => 0,
            'airport_fees' => 0,
            'congestion_charge' => 0,
            'buffer_amount' => 0,
            'total_price' => $cancellationFee,
        ];
    }

    private function captureCancellationPayment(Booking $booking): ?array
    {
        $latestPayment = BookingPayment::where('booking_id', $booking->id)
            ->latest()
            ->first();

        if (! $latestPayment) {
            return [
                'status' => 'skipped',
                'message' => 'No payment authorization found for cancellation capture.',
            ];
        }

        if ($latestPayment->status !== 'requires_capture') {
            return [
                'status' => 'skipped',
                'message' => 'Latest payment is not capturable.',
                'payment_status' => $latestPayment->status,
            ];
        }

        $finalAmount = (float) ($booking->final_price ?? 0);
        if ($finalAmount <= 0) {
            return [
                'status' => 'skipped',
                'message' => 'Cancellation fee is 0, capture was not attempted.',
            ];
        }

        if ($finalAmount > (float) $latestPayment->authorized_amount) {
            return [
                'status' => 'failed',
                'message' => 'Cancellation fee exceeds authorized amount.',
            ];
        }

        try {
            Stripe::setApiKey((string) config('services.stripe.secret_key'));
            $intent = PaymentIntent::retrieve($latestPayment->payment_intent_id);
            $capturedIntent = $intent->capture([
                'amount_to_capture' => (int) round($finalAmount * 100),
            ]);

            $latestPayment->captured_amount = $finalAmount;
            $latestPayment->amount_to_capture = $finalAmount;
            $latestPayment->status = $capturedIntent->status;
            $latestPayment->raw_payload = $capturedIntent->toArray();
            $latestPayment->save();

            $booking->payment_status = $capturedIntent->status === 'succeeded' ? 'paid' : $capturedIntent->status;
            $booking->save();

            return [
                'status' => 'captured',
                'captured_amount' => $finalAmount,
                'payment_status' => $latestPayment->status,
            ];
        } catch (ApiErrorException $e) {
            $latestPayment->failure_message = $e->getMessage();
            $latestPayment->status = 'failed';
            $latestPayment->save();

            $booking->payment_status = 'failed';
            $booking->save();

            return [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }
}
