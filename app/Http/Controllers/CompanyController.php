<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        $company = $request->user()->company;

        return response()->json([
            'data' => $company
        ]);
    }

    public function store(Request $request)
    {

    }

    public function show(Company $company)
    {
        return $company;
    }

    public function update(Request $request, Company $company)
    {

    }

    public function destroy(Company $company)
    {

    }
}