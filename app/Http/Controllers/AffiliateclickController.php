<?php

namespace App\Http\Controllers;

use App\Models\Affiliateclick;
use Illuminate\Http\Request;

class AffiliateclickController extends Controller
{
    public function index()
    {
        return Affiliateclick::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'affiliate_id' => 'required',
            'ip_address' => 'nullable|string|max:255',
            'user_agent' => 'nullable|string|max:255'
        ]);
        return Affiliateclick::create($request->only(['affiliate_id', 'ip_address', 'user_agent']));
    }

    public function show(Affiliateclick $affiliateclick)
    {
        return $affiliateclick;
    }

    public function update(Request $request, Affiliateclick $affiliateclick)
    {
        $validated = $request->validate([
            'affiliate_id' => 'required',
            'ip_address' => 'nullable|string|max:255',
            'user_agent' => 'nullable|string|max:255'
        ]);
        $affiliateclick->update($validated);
        return $affiliateclick;
    }

    public function destroy(Affiliateclick $affiliateclick)
    {
        $affiliateclick->delete();
        return response()->json(['message' => 'success'], 200);
    }
}