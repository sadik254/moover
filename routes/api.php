<?php

use App\Http\Controllers\UserController;
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

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Register and login routes for Users without authentication middleware
Route::post('user/register', [UserController::class, 'register']);
Route::post('user/login', [UserController::class, 'login']);
// Route::post('user/forgot-password', [UserController::class, 'forgotPassword']);

// Update user profile route with authentication middleware
Route::middleware('auth:sanctum')->post('/user/update', [UserController::class, 'update']);
Route::middleware('auth:sanctum')->get('user', [UserController::class, 'show']);
Route::middleware('auth:sanctum')->post('/user/logout', [UserController::class, 'logout']);
Route::middleware('auth:sanctum')->post('/user/update-password', [UserController::class, 'updatePassword']);

Route::middleware('auth:sanctum')->apiResource('companies', CompanyController::class);

Route::middleware('auth:sanctum')->apiResource('vehicles', VehicleController::class);

Route::middleware('auth:sanctum')->apiResource('drivers', DriverController::class);

Route::apiResource('customers', CustomerController::class);

Route::apiResource('bookings', BookingController::class);

Route::apiResource('formtemplates', FormtemplateController::class);

Route::apiResource('formsubmissions', FormsubmissionController::class);

Route::apiResource('affiliates', AffiliateController::class);

Route::apiResource('affiliateclicks', AffiliateclickController::class);
