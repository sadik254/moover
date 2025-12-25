<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        return Customer::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
            'phone' => 'required|string|max:255'
        ]);
        return Customer::create($request->only(['company_id', 'name', 'email', 'phone']));
    }

    public function show(Customer $customer)
    {
        return $customer;
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
            'phone' => 'required|string|max:255'
        ]);
        $customer->update($validated);
        return $customer;
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json(['message' => 'success'], 200);
    }
}