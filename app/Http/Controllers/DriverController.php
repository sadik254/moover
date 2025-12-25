<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index()
    {
        return Driver::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'vehicle_id' => 'nullable',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'license_number' => 'required|string|max:255',
            'available' => 'required|boolean'
        ]);
        return Driver::create($request->only(['company_id', 'vehicle_id', 'name', 'phone', 'license_number', 'available']));
    }

    public function show(Driver $driver)
    {
        return $driver;
    }

    public function update(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'vehicle_id' => 'nullable',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'license_number' => 'required|string|max:255',
            'available' => 'required|boolean'
        ]);
        $driver->update($validated);
        return $driver;
    }

    public function destroy(Driver $driver)
    {
        $driver->delete();
        return response()->json(['message' => 'success'], 200);
    }
}