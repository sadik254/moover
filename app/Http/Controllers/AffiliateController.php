<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    public function index()
    {
        return Affiliate::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'code' => 'nullable|string|max:255',
            'link' => 'nullable|string|max:255'
        ]);
        return Affiliate::create($request->only(['company_id', 'code', 'link']));
    }

    public function show(Affiliate $affiliate)
    {
        return $affiliate;
    }

    public function update(Request $request, Affiliate $affiliate)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'code' => 'nullable|string|max:255',
            'link' => 'nullable|string|max:255'
        ]);
        $affiliate->update($validated);
        return $affiliate;
    }

    public function destroy(Affiliate $affiliate)
    {
        $affiliate->delete();
        return response()->json(['message' => 'success'], 200);
    }
}