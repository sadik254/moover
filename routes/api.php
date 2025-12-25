<?php

use App\Http\Controllers\AffiliateclickController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\FormsubmissionController;
use App\Http\Controllers\FormtemplateController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\CompanyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('companies', CompanyController::class);

Route::apiResource('vehicles', VehicleController::class);

Route::apiResource('drivers', DriverController::class);

Route::apiResource('customers', CustomerController::class);

Route::apiResource('bookings', BookingController::class);

Route::apiResource('formtemplates', FormtemplateController::class);

Route::apiResource('formsubmissions', FormsubmissionController::class);

Route::apiResource('affiliates', AffiliateController::class);

Route::apiResource('affiliateclicks', AffiliateclickController::class);
