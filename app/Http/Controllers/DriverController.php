<?php

namespace App\Http\Controllers;

use App\Mail\DriverCreatedPasswordMail;
use App\Models\Company;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Uploadcare\Api;
use Uploadcare\Configuration;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $company = Company::first();

        if (! $company) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        }

        $drivers = Driver::where('company_id', $company->id)->get();

        return response()->json([
            'data' => $drivers
        ]);
    }

    public function store(Request $request)
    {
        $company = Company::first();

        if (! $company) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'vehicle_id' => [
                'nullable',
                Rule::exists('vehicles', 'id')
                    ->where('company_id', $company->id),
            ],
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|max:255|unique:drivers,email',
            'phone'            => 'required|string|max:255',
            'license_number'   => 'required|string|max:255',
            'license_expiry'   => 'nullable|date',
            'status'           => 'nullable|string|max:50',
            'employment_type'  => 'nullable|string|max:50',
            'commission'       => 'nullable|string|max:50',
            'license_front'    => 'nullable|file|image|max:5120',
            'license_back'     => 'nullable|file|image|max:5120',
            'address'          => 'nullable|string',
            'photo'            => 'nullable|file|image|max:5120',
            'available'        => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        $generatedPassword = Str::upper(Str::random(6));

        $data = $request->only([
            'vehicle_id',
            'name',
            'email',
            'phone',
            'license_number',
            'license_expiry',
            'status',
            'employment_type',
            'commission',
            'address',
            'available',
        ]);
        $data['company_id'] = $company->id;
        $data['password'] = Hash::make($generatedPassword);

        if ($request->hasFile('license_front')) {
            $configuration = Configuration::create(
                config('services.uploadcare.public_key'),
                config('services.uploadcare.secret_key')
            );

            $api = new Api($configuration);
            $file = $api->uploader()->fromPath(
                $request->file('license_front')->getPathname()
            );

            $data['license_front'] = "https://ucarecdn.com/{$file->getUuid()}/-/preview/";
        }

        if ($request->hasFile('license_back')) {
            $configuration = Configuration::create(
                config('services.uploadcare.public_key'),
                config('services.uploadcare.secret_key')
            );

            $api = new Api($configuration);
            $file = $api->uploader()->fromPath(
                $request->file('license_back')->getPathname()
            );

            $data['license_back'] = "https://ucarecdn.com/{$file->getUuid()}/-/preview/";
        }

        if ($request->hasFile('photo')) {
            $configuration = Configuration::create(
                config('services.uploadcare.public_key'),
                config('services.uploadcare.secret_key')
            );

            $api = new Api($configuration);
            $file = $api->uploader()->fromPath(
                $request->file('photo')->getPathname()
            );

            $data['photo'] = "https://ucarecdn.com/{$file->getUuid()}/-/preview/";
        }

        $driver = Driver::create($data);
        $this->sendDriverCredentialsEmail($driver, $generatedPassword);

        return response()->json([
            'message' => 'Driver created successfully',
            'data'    => $driver
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $driver = Driver::where('email', $request->email)->first();

        if (! $driver || ! Hash::check($request->password, (string) $driver->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $plainTextToken = $driver->createToken('DriverLogin', ['driver'])->plainTextToken;
        $token = explode('|', $plainTextToken)[1];

        return response()->json([
            'token' => $token,
            'driver_id' => $driver->id,
            'driver' => [
                'id' => $driver->id,
                'name' => $driver->name,
                'email' => $driver->email,
                'phone' => $driver->phone,
                'status' => $driver->status,
            ],
            'message' => 'Login successful',
        ], 200);
    }

    public function me(Request $request)
    {
        $driver = $request->user();

        if (! $driver instanceof Driver) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => [
                'id' => $driver->id,
                'name' => $driver->name,
                'email' => $driver->email,
                'phone' => $driver->phone,
                'status' => $driver->status,
                'employment_type' => $driver->employment_type,
                'license_expiry' => $driver->license_expiry,
                'address' => $driver->address,
                'photo' => $driver->photo,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $driver = $request->user();

        if (! $driver instanceof Driver) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $driver->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    public function show(Request $request, $id)
    {
        $company = Company::first();

        if (! $company) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        }

        $driver = Driver::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (! $driver) {
            return response()->json([
                'message' => 'Driver not found'
            ], 404);
        }

        return response()->json([
            'data' => $driver
        ]);
    }

    public function update(Request $request, $id)
    {
        $company = Company::first();

        if (! $company) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        }

        $driver = Driver::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (! $driver) {
            return response()->json([
                'message' => 'Driver not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'vehicle_id' => [
                'sometimes',
                'nullable',
                Rule::exists('vehicles', 'id')
                    ->where('company_id', $company->id),
            ],
            'name'             => 'sometimes|required|string|max:255',
            'email'            => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('drivers', 'email')->ignore($driver->id),
            ],
            'phone'            => 'sometimes|required|string|max:255',
            'license_number'   => 'sometimes|required|string|max:255',
            'license_expiry'   => 'sometimes|nullable|date',
            'status'           => 'sometimes|nullable|string|max:50',
            'employment_type'  => 'sometimes|nullable|string|max:50',
            'commission'       => 'sometimes|nullable|string|max:50',
            'license_front'    => 'sometimes|nullable|file|image|max:5120',
            'license_back'     => 'sometimes|nullable|file|image|max:5120',
            'address'          => 'sometimes|nullable|string',
            'photo'            => 'sometimes|nullable|file|image|max:5120',
            'available'        => 'sometimes|nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('license_front')) {
            $configuration = Configuration::create(
                config('services.uploadcare.public_key'),
                config('services.uploadcare.secret_key')
            );

            $api = new Api($configuration);
            $file = $api->uploader()->fromPath(
                $request->file('license_front')->getPathname()
            );

            $driver->license_front = "https://ucarecdn.com/{$file->getUuid()}/-/preview/";
        }

        if ($request->hasFile('license_back')) {
            $configuration = Configuration::create(
                config('services.uploadcare.public_key'),
                config('services.uploadcare.secret_key')
            );

            $api = new Api($configuration);
            $file = $api->uploader()->fromPath(
                $request->file('license_back')->getPathname()
            );

            $driver->license_back = "https://ucarecdn.com/{$file->getUuid()}/-/preview/";
        }

        if ($request->hasFile('photo')) {
            $configuration = Configuration::create(
                config('services.uploadcare.public_key'),
                config('services.uploadcare.secret_key')
            );

            $api = new Api($configuration);
            $file = $api->uploader()->fromPath(
                $request->file('photo')->getPathname()
            );

            $driver->photo = "https://ucarecdn.com/{$file->getUuid()}/-/preview/";
        }

        $driver->fill(
            $request->only([
                'vehicle_id',
                'name',
                'email',
                'phone',
                'license_number',
                'license_expiry',
                'status',
                'employment_type',
                'commission',
                'address',
                'available',
            ])
        );

        $driver->save();

        return response()->json([
            'message' => 'Driver updated successfully',
            'data'    => $driver
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $company = Company::first();

        if (! $company) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        }

        $driver = Driver::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (! $driver) {
            return response()->json([
                'message' => 'Driver not found'
            ], 404);
        }

        $driver->delete();

        return response()->json([
            'message' => 'Driver deleted successfully'
        ], 200);
    }

    private function sendDriverCredentialsEmail(Driver $driver, string $generatedPassword): void
    {
        try {
            Mail::to($driver->email)->send(new DriverCreatedPasswordMail(
                driver: $driver,
                generatedPassword: $generatedPassword
            ));
        } catch (\Throwable $e) {
            Log::warning('Driver credentials mail failed', [
                'driver_id' => $driver->id,
                'email' => $driver->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
