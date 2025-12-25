<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        return Company::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:255',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date'
        ]);
        return Company::create($request->only(['name', 'email', 'phone', 'address', 'timezone', 'created_at', 'updated_at']));
    }

    public function show(Company $company)
    {
        return $company;
    }

    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:255',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date'
        ]);
        $company->update($validated);
        return $company;
    }

    public function destroy(Company $company)
    {
        $company->delete();
        return response()->json(['message' => 'success'], 200);
    }
}