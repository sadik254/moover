<?php

namespace App\Http\Controllers;

use App\Models\Formtemplate;
use Illuminate\Http\Request;

class FormtemplateController extends Controller
{
    public function index()
    {
        return Formtemplate::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable',
            'name' => 'required|string|max:255',
            'schema_json' => 'required'
        ]);
        return Formtemplate::create($request->only(['company_id', 'name', 'schema_json']));
    }

    public function show(Formtemplate $formtemplate)
    {
        return $formtemplate;
    }

    public function update(Request $request, Formtemplate $formtemplate)
    {
        $validated = $request->validate([
            'company_id' => 'nullable',
            'name' => 'required|string|max:255',
            'schema_json' => 'required'
        ]);
        $formtemplate->update($validated);
        return $formtemplate;
    }

    public function destroy(Formtemplate $formtemplate)
    {
        $formtemplate->delete();
        return response()->json(['message' => 'success'], 200);
    }
}