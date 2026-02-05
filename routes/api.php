<?php

use App\Http\Controllers\VehicleClassController;
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


// Register and login routes for Users without authentication middleware
Route::post('user/register', [UserController::class, 'register']);
Route::post('user/login', [UserController::class, 'login']);
// Route::post('user/forgot-password', [UserController::class, 'forgotPassword']);

// Update user profile route with authentication middleware
Route::middleware('auth:sanctum')->post('/user/update', [UserController::class, 'update']);
Route::middleware('auth:sanctum')->get('user', [UserController::class, 'show']);
Route::middleware('auth:sanctum')->post('/user/logout', [UserController::class, 'logout']);
Route::middleware('auth:sanctum')->post('/user/update-password', [UserController::class, 'updatePassword']);

// Authenticated Company Routes
Route::middleware('auth:sanctum')->post('/company', [CompanyController::class, 'store']);
Route::middleware('auth:sanctum')->get('/company', [CompanyController::class, 'index']);
Route::middleware('auth:sanctum')->post('/company/update', [CompanyController::class, 'update']);
Route::middleware('auth:sanctum')->delete('/company', [CompanyController::class, 'destroy']);

// Authenticated Vehicle Class Routes
Route::middleware('auth:sanctum')->get('/vehicle-classes', [VehicleClassController::class, 'index']);
Route::middleware('auth:sanctum')->post('/vehicle-classes', [VehicleClassController::class, 'store']);
Route::middleware('auth:sanctum')->get('/vehicle-classes/{id}', [VehicleClassController::class, 'show']);
Route::middleware('auth:sanctum')->post('/vehicle-classes/update/{id}', [VehicleClassController::class, 'update']);
Route::middleware('auth:sanctum')->delete('/vehicle-classes/{id}', [VehicleClassController::class, 'destroy']);

// Authenticated Vehicle Routes
Route::middleware('auth:sanctum')->get('/vehicles', [VehicleController::class, 'index']);
Route::middleware('auth:sanctum')->post('/vehicles', [VehicleController::class, 'store']);
Route::middleware('auth:sanctum')->get('/vehicles/{id}', [VehicleController::class, 'show']);
Route::middleware('auth:sanctum')->post('/vehicles/update/{id}', [VehicleController::class, 'update']);
Route::middleware('auth:sanctum')->delete('/vehicles/{id}', [VehicleController::class, 'destroy']);

Route::middleware('auth:sanctum')->apiResource('drivers', DriverController::class);

Route::apiResource('customers', CustomerController::class);

Route::apiResource('bookings', BookingController::class);

Route::apiResource('formtemplates', FormtemplateController::class);

Route::apiResource('formsubmissions', FormsubmissionController::class);

Route::apiResource('affiliates', AffiliateController::class);

Route::apiResource('affiliateclicks', AffiliateclickController::class);

Route::apiResource('vehicle_classes', VehicleClassController::class);
