<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Uploadcare\Api;
use Uploadcare\Configuration;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => $request->user()->company
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // Enforce 1 user → 1 company
        if ($user->company) {
            return response()->json([
                'message' => 'You already have a company'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'phone'    => 'nullable|string|max:50',
            'address'  => 'nullable|string',
            'timezone' => 'nullable|string|max:100',
            'logo'     => 'nullable|file|image|max:5120',
            'url'      => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        $company = new Company();
        $company->user_id = $user->id;

        // Uploadcare logo upload
        if ($request->hasFile('logo')) {
            $configuration = Configuration::create(
                config('services.uploadcare.public_key'),
                config('services.uploadcare.secret_key')
            );

            $api = new Api($configuration);

            $file = $api->uploader()->fromPath(
                $request->file('logo')->getPathname()
            );

            $company->logo = "https://ucarecdn.com/{$file->getUuid()}/-/preview/";
        }

        $company->fill($request->only([
            'name',
            'email',
            'phone',
            'address',
            'timezone',
            'url',
        ]));

        $company->save();

        return response()->json([
            'message' => 'Company created successfully',
            'data'    => $company
        ], 201);
    }

    public function show(Request $request)
    {
        $company = $request->user()->company;

        if (! $company) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        }

        return response()->json([
            'data' => $company
        ]);
    }

    public function update(Request $request)
    {
        $company = $request->user()->company;

        if (! $company) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        }

        // dd($company);

        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|required|string|max:255',
            'email'    => 'sometimes|required|email|max:255',
            'phone'    => 'sometimes|nullable|string|max:50',
            'address'  => 'sometimes|nullable|string',
            'timezone' => 'sometimes|nullable|string|max:100',
            'logo'     => 'sometimes|nullable|file|image|max:5120',
            'url'      => 'sometimes|nullable|string|max:255',
        ]);

        // dd($request->all());

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Uploadcare logo update (only if sent)
        if ($request->hasFile('logo')) {
            $configuration = Configuration::create(
                config('services.uploadcare.public_key'),
                config('services.uploadcare.secret_key')
            );

            $api = new Api($configuration);

            $file = $api->uploader()->fromPath(
                $request->file('logo')->getPathname()
            );

            $company->logo = "https://ucarecdn.com/{$file->getUuid()}/-/preview/";
        }

        // dd($request->all());

        // Update only provided fields
        $company->fill(
            $request->only([
                'name',
                'email',
                'phone',
                'address',
                'timezone',
                'url',
            ])
        );

        // dd($company);

        $company->save();

        return response()->json([
            'message' => 'Company updated successfully',
            'data'    => $company
        ], 200);
    }

    public function destroy(Request $request)
    {
        $company = $request->user()->company;

        if (! $company) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        }

        $company->delete();

        return response()->json([
            'message' => 'Company deleted successfully'
        ]);
    }
}