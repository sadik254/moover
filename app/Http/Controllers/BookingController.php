<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
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
            'customer_id'   => ['nullable', Rule::exists('customers', 'id')],
            'vehicle_id'    => ['required', Rule::exists('vehicles', 'id')],
            'driver_id'     => ['nullable', Rule::exists('drivers', 'id')],
            'service_type'  => ['required', Rule::in(['point_to_point', 'hourly', 'airport', 'custom'])],
            'pickup_address' => 'required|string',
            'dropoff_address' => 'nullable|string',
            'pickup_time'   => 'required|date',
            'passengers'    => 'required|integer|min:1',
            'child_seats'   => 'nullable|integer|min:0',
            'bags'          => 'nullable|integer|min:0',
            'flight_number' => 'nullable|string|max:100',
            'airlines'      => 'nullable|string|max:100',
            'distance_km'   => 'required|numeric|min:0',
            'base_price'    => 'required|numeric|min:0',
            'extras_price'  => 'nullable|numeric|min:0',
            'taxes'         => 'nullable|numeric|min:0',
            'gratuity'      => 'nullable|numeric|min:0',
            'parking'       => 'nullable|numeric|min:0',
            'others'        => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:100',
            'payment_status' => 'nullable|string|max:100',
            'status'        => ['nullable', Rule::in(['pending', 'confirmed', 'assigned', 'on_route', 'completed', 'cancelled'])],
            'notes'         => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $vehicle = Vehicle::find($request->vehicle_id);
        $rate = 0;

        switch ($request->service_type) {
            case 'hourly':
                $rate = (float) ($vehicle->hourly_rate ?? 0);
                break;
            case 'airport':
                $rate = (float) ($vehicle->airport_rate ?? 0);
                break;
            case 'point_to_point':
            case 'custom':
            default:
                $rate = (float) ($vehicle->per_km_rate ?? 0);
                break;
        }

        $distance = (float) $request->distance_km;
        $base = (float) $request->base_price;
        $total = $base + ($distance * $rate);

        $data = $request->only([
            'customer_id',
            'vehicle_id',
            'driver_id',
            'service_type',
            'pickup_address',
            'dropoff_address',
            'pickup_time',
            'passengers',
            'child_seats',
            'bags',
            'flight_number',
            'airlines',
            'distance_km',
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

        $authUser = $request->user();
        if ($authUser instanceof Customer) {
            $data['customer_id'] = $authUser->id;
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
            'customer_id'   => ['sometimes', 'nullable', Rule::exists('customers', 'id')],
            'vehicle_id'    => ['sometimes', Rule::exists('vehicles', 'id')],
            'driver_id'     => ['sometimes', 'nullable', Rule::exists('drivers', 'id')],
            'service_type'  => ['sometimes', Rule::in(['point_to_point', 'hourly', 'airport', 'custom'])],
            'pickup_address' => 'sometimes|required|string',
            'dropoff_address' => 'sometimes|nullable|string',
            'pickup_time'   => 'sometimes|required|date',
            'passengers'    => 'sometimes|required|integer|min:1',
            'child_seats'   => 'sometimes|nullable|integer|min:0',
            'bags'          => 'sometimes|nullable|integer|min:0',
            'flight_number' => 'sometimes|nullable|string|max:100',
            'airlines'      => 'sometimes|nullable|string|max:100',
            'distance_km'   => 'sometimes|required|numeric|min:0',
            'base_price'    => 'sometimes|required|numeric|min:0',
            'extras_price'  => 'sometimes|nullable|numeric|min:0',
            'taxes'         => 'sometimes|nullable|numeric|min:0',
            'gratuity'      => 'sometimes|nullable|numeric|min:0',
            'parking'       => 'sometimes|nullable|numeric|min:0',
            'others'        => 'sometimes|nullable|numeric|min:0',
            'payment_method' => 'sometimes|nullable|string|max:100',
            'payment_status' => 'sometimes|nullable|string|max:100',
            'status'        => ['sometimes', Rule::in(['pending', 'confirmed', 'assigned', 'on_route', 'completed', 'cancelled'])],
            'notes'         => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $booking->fill($request->only([
            'customer_id',
            'vehicle_id',
            'driver_id',
            'service_type',
            'pickup_address',
            'dropoff_address',
            'pickup_time',
            'passengers',
            'child_seats',
            'bags',
            'flight_number',
            'airlines',
            'distance_km',
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

        $recalc = $request->hasAny(['vehicle_id', 'service_type', 'distance_km', 'base_price']);
        if ($recalc) {
            $vehicle = Vehicle::find($booking->vehicle_id);
            $rate = 0;

            switch ($booking->service_type) {
                case 'hourly':
                    $rate = (float) ($vehicle->hourly_rate ?? 0);
                    break;
                case 'airport':
                    $rate = (float) ($vehicle->airport_rate ?? 0);
                    break;
                case 'point_to_point':
                case 'custom':
                default:
                    $rate = (float) ($vehicle->per_km_rate ?? 0);
                    break;
            }

            $distance = (float) $booking->distance_km;
            $base = (float) $booking->base_price;
            $booking->total_price = $base + ($distance * $rate);
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
