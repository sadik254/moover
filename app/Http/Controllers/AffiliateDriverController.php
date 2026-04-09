<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\AffiliateDriver;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AffiliateDriverController extends Controller
{
    public function index(Request $request)
    {
        $affiliate = $this->resolveAffiliate($request);
        if (! $affiliate) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'per_page' => 'sometimes|integer|min:1|max:100',
            'status' => 'sometimes|nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = AffiliateDriver::where('affiliate_id', $affiliate->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->input('per_page', 15);
        $drivers = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        return response()->json(['data' => $drivers]);
    }

    public function store(Request $request)
    {
        $affiliate = $this->resolveAffiliate($request);
        if (! $affiliate) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('affiliate_drivers', 'email')->where('affiliate_id', $affiliate->id),
            ],
            'phone' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'license_number' => 'nullable|string|max:255',
            'license_expiry' => 'nullable|date',
            'employment_type' => 'nullable|string|max:50',
            'commission' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'photo' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $driver = AffiliateDriver::create([
            'affiliate_id' => $affiliate->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'status' => $request->status,
            'license_number' => $request->license_number,
            'license_expiry' => $request->license_expiry,
            'employment_type' => $request->employment_type,
            'commission' => $request->commission,
            'address' => $request->address,
            'photo' => $request->photo,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Affiliate driver created successfully',
            'data' => $driver,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $affiliate = $this->resolveAffiliate($request);
        if (! $affiliate) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $driver = AffiliateDriver::where('affiliate_id', $affiliate->id)
            ->where('id', $id)
            ->first();

        if (! $driver) {
            return response()->json(['message' => 'Affiliate driver not found'], 404);
        }

        return response()->json(['data' => $driver]);
    }

    public function update(Request $request, $id)
    {
        $affiliate = $this->resolveAffiliate($request);
        if (! $affiliate) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $driver = AffiliateDriver::where('affiliate_id', $affiliate->id)
            ->where('id', $id)
            ->first();

        if (! $driver) {
            return response()->json(['message' => 'Affiliate driver not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('affiliate_drivers', 'email')
                    ->where('affiliate_id', $affiliate->id)
                    ->ignore($driver->id),
            ],
            'phone' => 'sometimes|nullable|string|max:255',
            'status' => 'sometimes|nullable|string|max:50',
            'license_number' => 'sometimes|nullable|string|max:255',
            'license_expiry' => 'sometimes|nullable|date',
            'employment_type' => 'sometimes|nullable|string|max:50',
            'commission' => 'sometimes|nullable|string|max:50',
            'address' => 'sometimes|nullable|string|max:255',
            'photo' => 'sometimes|nullable|string|max:255',
            'notes' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $driver->fill($request->only([
            'name',
            'email',
            'phone',
            'status',
            'license_number',
            'license_expiry',
            'employment_type',
            'commission',
            'address',
            'photo',
            'notes',
        ]));
        $driver->save();

        return response()->json([
            'message' => 'Affiliate driver updated successfully',
            'data' => $driver,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $affiliate = $this->resolveAffiliate($request);
        if (! $affiliate) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $driver = AffiliateDriver::where('affiliate_id', $affiliate->id)
            ->where('id', $id)
            ->first();

        if (! $driver) {
            return response()->json(['message' => 'Affiliate driver not found'], 404);
        }

        $driver->delete();

        return response()->json(['message' => 'Affiliate driver deleted successfully']);
    }

    private function resolveAffiliate(Request $request): ?Affiliate
    {
        $user = $request->user();
        if (! $user instanceof User || (string) $user->user_type !== 'affiliate') {
            return null;
        }

        return Affiliate::where('user_id', $user->id)->first();
    }
}
