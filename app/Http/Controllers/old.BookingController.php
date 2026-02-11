<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        $company = Company::first();

        if (! $company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        $bookings = Booking::where('company_id', $company->id)->get();

        return response()->json(['data' => $bookings]);
    }

    public function store(Request $request)
    {
        $company = Company::first();

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
            'distance_km'     => 'required|numeric|min:0',
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

        $pickup = Carbon::parse($request->pickup_time);
        $dropoff = $request->filled('dropoff_time') ? Carbon::parse($request->dropoff_time) : null;

        $windowStart = $dropoff ? $pickup : $pickup->copy()->subHours(2);
        $windowEnd = $dropoff ? $dropoff : $pickup->copy()->addHours(2);

        $unavailableVehicleIds = Booking::where('company_id', $company->id)
            ->where(function ($query) use ($windowStart, $windowEnd) {
                $query->where(function ($q) use ($windowStart, $windowEnd) {
                    $q->whereNotNull('dropoff_time')
                        ->where('pickup_time', '<=', $windowEnd)
                        ->where('dropoff_time', '>=', $windowStart);
                })->orWhere(function ($q) use ($windowStart, $windowEnd) {
                    $q->whereNull('dropoff_time')
                        ->whereBetween('pickup_time', [$windowStart, $windowEnd]);
                });
            })
            ->pluck('vehicle_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $vehicles = Vehicle::with('vehicleClass:id,name')
            ->whereNotIn('id', $unavailableVehicleIds)
            ->get();

        $passengers = (int) $request->passengers;
        $minCap = $passengers + 2;
        $maxCap = $passengers + 4;

        $vehicleOptions = $vehicles->map(function ($vehicle) use ($request, $minCap, $maxCap) {
            $rate = 0;
            $units = 0;

            switch ($request->service_type) {
                case 'hourly':
                    $rate = (float) ($vehicle->hourly_rate ?? 0);
                    $units = (float) ($request->hours ?? 0);
                    break;
                case 'airport':
                    $rate = (float) ($vehicle->airport_rate ?? 0);
                    $units = (float) ($request->distance_km ?? 0);
                    break;
                case 'point_to_point':
                case 'custom':
                default:
                    $rate = (float) ($vehicle->per_km_rate ?? 0);
                    $units = (float) ($request->distance_km ?? 0);
                    break;
            }

            $base = (float) $request->base_price;
            $extras = (float) ($request->extras_price ?? 0);
            $taxes = (float) ($request->taxes ?? 0);
            $gratuity = (float) ($request->gratuity ?? 0);
            $parking = (float) ($request->parking ?? 0);
            $others = (float) ($request->others ?? 0);

            $total = $base + ($units * $rate) + $extras + $taxes + $gratuity + $parking + $others;

            $capacity = (int) ($vehicle->capacity ?? 0);
            $recommended = $capacity >= $minCap && $capacity <= $maxCap;

            return [
                'vehicle_id' => $vehicle->id,
                'name' => $vehicle->name,
                'class' => $vehicle->vehicleClass?->name,
                'image' => $vehicle->image,
                'capacity' => $vehicle->capacity,
                'rate' => $rate,
                'base_price' => $base,
                'distance_km' => (float) ($request->distance_km ?? 0),
                'hours' => (float) ($request->hours ?? 0),
                'total_price' => $total,
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

        $rate = 0;
        $units = 0;
        switch ($request->service_type) {
            case 'hourly':
                $rate = (float) ($vehicle->hourly_rate ?? 0);
                $units = (float) ($request->hours ?? 0);
                break;
            case 'airport':
                $rate = (float) ($vehicle->airport_rate ?? 0);
                $units = (float) ($request->distance_km ?? 0);
                break;
            case 'point_to_point':
            case 'custom':
            default:
                $rate = (float) ($vehicle->per_km_rate ?? 0);
                $units = (float) ($request->distance_km ?? 0);
                break;
        }

        $base = (float) $request->base_price;
        $extras = (float) ($request->extras_price ?? 0);
        $taxes = (float) ($request->taxes ?? 0);
        $gratuity = (float) ($request->gratuity ?? 0);
        $parking = (float) ($request->parking ?? 0);
        $others = (float) ($request->others ?? 0);

        $total = $base + ($units * $rate) + $extras + $taxes + $gratuity + $parking + $others;

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
        $data['total_price'] = $total;
        $data['final_price'] = $total;

        $authUser = $request->user();
        if ($authUser instanceof Customer) {
            $data['customer_id'] = $authUser->id;
            $data['name'] = $authUser->name;
            $data['email'] = $authUser->email;
            $data['phone'] = $authUser->phone;
        }

        $booking = Booking::create($data);

        return response()->json([
            'message' => 'Booking created successfully',
            'data' => $booking,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $company = Company::first();

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

        $company = Company::first();

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
            'distance_km'     => 'sometimes|required|numeric|min:0',
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

        if ($request->service_type === 'hourly' && ! $request->filled('hours')) {
            return response()->json([
                'message' => 'Hours is required for hourly service'
            ], 422);
        }

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
            $rate = 0;
            $units = 0;

            switch ($booking->service_type) {
                case 'hourly':
                    $rate = (float) ($vehicle->hourly_rate ?? 0);
                    $units = (float) ($booking->hours ?? 0);
                    break;
                case 'airport':
                    $rate = (float) ($vehicle->airport_rate ?? 0);
                    $units = (float) ($booking->distance_km ?? 0);
                    break;
                case 'point_to_point':
                case 'custom':
                default:
                    $rate = (float) ($vehicle->per_km_rate ?? 0);
                    $units = (float) ($booking->distance_km ?? 0);
                    break;
            }

            $base = (float) $booking->base_price;
            $extras = (float) ($booking->extras_price ?? 0);
            $taxes = (float) ($booking->taxes ?? 0);
            $gratuity = (float) ($booking->gratuity ?? 0);
            $parking = (float) ($booking->parking ?? 0);
            $others = (float) ($booking->others ?? 0);

            $booking->total_price = $base + ($units * $rate) + $extras + $taxes + $gratuity + $parking + $others;
            $booking->final_price = $booking->total_price;
        }

        $booking->save();

        return response()->json([
            'message' => 'Booking updated successfully',
            'data' => $booking,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $company = Company::first();

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
}
