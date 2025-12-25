<?php

namespace App\Http\Controllers;

use App\Models\Formsubmission;
use Illuminate\Http\Request;

class FormsubmissionController extends Controller
{
    public function index()
    {
        return Formsubmission::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'form_id' => 'required',
            'data_json' => 'required'
        ]);
        return Formsubmission::create($request->only(['company_id', 'form_id', 'data_json']));
    }

    public function show(Formsubmission $formsubmission)
    {
        return $formsubmission;
    }

    public function update(Request $request, Formsubmission $formsubmission)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'form_id' => 'required',
            'data_json' => 'required'
        ]);
        $formsubmission->update($validated);
        return $formsubmission;
    }

    public function destroy(Formsubmission $formsubmission)
    {
        $formsubmission->delete();
        return response()->json(['message' => 'success'], 200);
    }
}