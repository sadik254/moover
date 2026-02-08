<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    // Admin: list customers for company
    public function index(Request $request)
    {
        $company = $request->user()->company;

        if (! $company) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        }

        $customers = Customer::where('company_id', $company->id)->get();

        return response()->json([
            'data' => $customers
        ]);
    }

    // Admin: create customer with auto-generated password
    public function store(Request $request)
    {
        $company = $request->user()->company;

        if (! $company) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'                    => 'nullable|string|max:255',
            'email'                   => 'nullable|email|max:255|unique:customers,email',
            'phone'                   => 'required|string|max:255',
            'customer_company'        => 'nullable|string|max:255',
            'customer_type'           => 'nullable|string|max:100',
            'dispatch_note'           => 'nullable|string',
            'preferred_service_level' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        $generatedPassword = Str::upper(Str::random(6));

        $data = $request->only([
            'name',
            'email',
            'phone',
            'customer_company',
            'customer_type',
            'dispatch_note',
            'preferred_service_level',
        ]);
        $data['company_id'] = $company->id;
        $data['password'] = Hash::make($generatedPassword);

        $customer = Customer::create($data);

        return response()->json([
            'message' => 'Customer created successfully',
            'data' => $customer,
            'generated_password' => $generatedPassword
        ], 201);
    }

    // Customer: self register with own password
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id'              => 'required|exists:companies,id',
            'name'                    => 'required|string|max:255',
            'email'                   => 'required|email|max:255|unique:customers,email',
            'phone'                   => 'required|string|max:255',
            'password'                => 'required|min:6|confirmed',
            'customer_company'        => 'nullable|string|max:255',
            'customer_type'           => 'nullable|string|max:100',
            'dispatch_note'           => 'nullable|string',
            'preferred_service_level' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'company_id',
            'name',
            'email',
            'phone',
            'customer_company',
            'customer_type',
            'dispatch_note',
            'preferred_service_level',
        ]);
        $data['password'] = Hash::make($request->password);

        $customer = Customer::create($data);

        $plainTextToken = $customer->createToken('CustomerRegistration', ['customer'])->plainTextToken;
        $token = explode('|', $plainTextToken)[1];

        return response()->json([
            'token' => $token,
            'customer_id' => $customer->id,
            'message' => 'Customer registered successfully',
        ], 201);
    }

    // Customer: login to get token with ability
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $customer = Customer::where('email', $request->email)->first();

        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $plainTextToken = $customer->createToken('CustomerLogin', ['customer'])->plainTextToken;
        $token = explode('|', $plainTextToken)[1];

        return response()->json([
            'token' => $token,
            'customer_id' => $customer->id,
            'message' => 'Login successful',
        ], 200);
    }

    public function show(Request $request, $id)
    {
        $company = $request->user()->company;

        if (! $company) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        }

        $customer = Customer::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (! $customer) {
            return response()->json([
                'message' => 'Customer not found'
            ], 404);
        }

        return response()->json([
            'data' => $customer
        ]);
    }

    public function update(Request $request, $id)
    {
        $company = $request->user()->company;

        if (! $company) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        }

        $customer = Customer::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (! $customer) {
            return response()->json([
                'message' => 'Customer not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'                    => 'sometimes|nullable|string|max:255',
            'email'                   => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($customer->id),
            ],
            'phone'                   => 'sometimes|nullable|string|max:255',
            'customer_company'        => 'sometimes|nullable|string|max:255',
            'customer_type'           => 'sometimes|nullable|string|max:100',
            'dispatch_note'           => 'sometimes|nullable|string',
            'preferred_service_level' => 'sometimes|nullable|string|max:100',
            'password'                => 'sometimes|nullable|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        $customer->fill(
            $request->only([
                'name',
                'email',
                'phone',
                'customer_company',
                'customer_type',
                'dispatch_note',
                'preferred_service_level',
            ])
        );

        if ($request->filled('password')) {
            $customer->password = Hash::make($request->password);
        }

        $customer->save();

        return response()->json([
            'message' => 'Customer updated successfully',
            'data' => $customer
        ], 200);
    }

    public function destroy(Request $request, $id)
    {
        $company = $request->user()->company;

        if (! $company) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        }

        $customer = Customer::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (! $customer) {
            return response()->json([
                'message' => 'Customer not found'
            ], 404);
        }

        $customer->delete();

        return response()->json([
            'message' => 'Customer deleted successfully'
        ], 200);
    }

    // Customer: logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}
