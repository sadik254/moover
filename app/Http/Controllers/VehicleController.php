<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index()
    {
        return Vehicle::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'capacity' => 'required',
            'luggage' => 'required',
            'hourly_rate' => 'nullable|string|max:255',
            'per_km_rate' => 'nullable|string|max:255',
            'airport_rate' => 'nullable|string|max:255'
        ]);
        return Vehicle::create($request->only(['company_id', 'name', 'category', 'capacity', 'luggage', 'hourly_rate', 'per_km_rate', 'airport_rate']));
    }

    public function show(Vehicle $vehicle)
    {
        return $vehicle;
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'capacity' => 'required',
            'luggage' => 'required',
            'hourly_rate' => 'nullable|string|max:255',
            'per_km_rate' => 'nullable|string|max:255',
            'airport_rate' => 'nullable|string|max:255'
        ]);
        $vehicle->update($validated);
        return $vehicle;
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return response()->json(['message' => 'success'], 200);
    }
}