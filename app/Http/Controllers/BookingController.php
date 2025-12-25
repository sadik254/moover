<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index()
    {
        return Booking::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'customer_id' => 'nullable',
            'vehicle_id' => 'nullable',
            'driver_id' => 'nullable',
            'service_type' => 'nullable',
            ''hourly'' => 'nullable|string|max:255',
            ''airport'' => 'nullable|string|max:255',
            ''custom')' => 'nullable|string|max:255',
            'pickup_address' => 'nullable|string|max:255',
            'dropoff_address' => 'nullable|string|max:255',
            'pickup_time' => 'nullable|date',
            'passengers' => 'nullable|integer',
            'distance_km' => 'nullable|string|max:255',
            'base_price' => 'nullable|string|max:255',
            'extras_price' => 'nullable|string|max:255',
            'total_price' => 'nullable|string|max:255',
            'status' => 'nullable',
            ''confirmed'' => 'nullable|string|max:255',
            ''assigned'' => 'nullable|string|max:255',
            ''on_route'' => 'nullable|string|max:255',
            ''completed'' => 'nullable|string|max:255',
            ''cancelled')' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255'
        ]);
        return Booking::create($request->only(['company_id', 'customer_id', 'vehicle_id', 'driver_id', 'service_type', ''hourly'', ''airport'', ''custom')', 'pickup_address', 'dropoff_address', 'pickup_time', 'passengers', 'distance_km', 'base_price', 'extras_price', 'total_price', 'status', ''confirmed'', ''assigned'', ''on_route'', ''completed'', ''cancelled')', 'notes']));
    }

    public function show(Booking $booking)
    {
        return $booking;
    }

    public function update(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'customer_id' => 'nullable',
            'vehicle_id' => 'nullable',
            'driver_id' => 'nullable',
            'service_type' => 'nullable',
            ''hourly'' => 'nullable|string|max:255',
            ''airport'' => 'nullable|string|max:255',
            ''custom')' => 'nullable|string|max:255',
            'pickup_address' => 'nullable|string|max:255',
            'dropoff_address' => 'nullable|string|max:255',
            'pickup_time' => 'nullable|date',
            'passengers' => 'nullable|integer',
            'distance_km' => 'nullable|string|max:255',
            'base_price' => 'nullable|string|max:255',
            'extras_price' => 'nullable|string|max:255',
            'total_price' => 'nullable|string|max:255',
            'status' => 'nullable',
            ''confirmed'' => 'nullable|string|max:255',
            ''assigned'' => 'nullable|string|max:255',
            ''on_route'' => 'nullable|string|max:255',
            ''completed'' => 'nullable|string|max:255',
            ''cancelled')' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255'
        ]);
        $booking->update($validated);
        return $booking;
    }

    public function destroy(Booking $booking)
    {
        $booking->delete();
        return response()->json(['message' => 'success'], 200);
    }
}