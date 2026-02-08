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
Route::middleware(['auth:sanctum', 'user.only'])->post('/user/update', [UserController::class, 'update']);
Route::middleware(['auth:sanctum', 'user.only'])->get('user', [UserController::class, 'show']);
Route::middleware(['auth:sanctum', 'user.only'])->post('/user/logout', [UserController::class, 'logout']);
Route::middleware(['auth:sanctum', 'user.only'])->post('/user/update-password', [UserController::class, 'updatePassword']);

// Authenticated Company Routes
Route::middleware(['auth:sanctum', 'user.only'])->post('/company', [CompanyController::class, 'store']);
Route::middleware(['auth:sanctum', 'user.only'])->get('/company', [CompanyController::class, 'index']);
Route::middleware(['auth:sanctum', 'user.only'])->post('/company/update', [CompanyController::class, 'update']);
Route::middleware(['auth:sanctum', 'user.only'])->delete('/company', [CompanyController::class, 'destroy']);

// Authenticated Vehicle Class Routes
Route::middleware(['auth:sanctum', 'user.only'])->get('/vehicle-classes', [VehicleClassController::class, 'index']);
Route::middleware(['auth:sanctum', 'user.only'])->post('/vehicle-classes', [VehicleClassController::class, 'store']);
Route::middleware(['auth:sanctum', 'user.only'])->get('/vehicle-classes/{id}', [VehicleClassController::class, 'show']);
Route::middleware(['auth:sanctum', 'user.only'])->post('/vehicle-classes/update/{id}', [VehicleClassController::class, 'update']);
Route::middleware(['auth:sanctum', 'user.only'])->delete('/vehicle-classes/{id}', [VehicleClassController::class, 'destroy']);

// Authenticated Vehicle Routes
Route::middleware(['auth:sanctum', 'user.only'])->get('/vehicles', [VehicleController::class, 'index']);
Route::middleware(['auth:sanctum', 'user.only'])->post('/vehicles', [VehicleController::class, 'store']);
Route::middleware(['auth:sanctum', 'user.only'])->get('/vehicles/{id}', [VehicleController::class, 'show']);
Route::middleware(['auth:sanctum', 'user.only'])->post('/vehicles/update/{id}', [VehicleController::class, 'update']);
Route::middleware(['auth:sanctum', 'user.only'])->delete('/vehicles/{id}', [VehicleController::class, 'destroy']);

// Authenticated Driver Routes For Admin & Dispatcher
Route::middleware(['auth:sanctum', 'user.only'])->get('drivers', [DriverController::class, 'index']);
Route::middleware(['auth:sanctum', 'user.only'])->post('drivers', [DriverController::class, 'store']);
Route::middleware(['auth:sanctum', 'user.only'])->get('drivers/{id}', [DriverController::class, 'show']);
Route::middleware(['auth:sanctum', 'user.only'])->post('drivers/update/{id}', [DriverController::class, 'update']);
Route::middleware(['auth:sanctum', 'user.only'])->delete('drivers/{id}', [DriverController::class, 'destroy']);

// Customer auth routes (abilities-based)
Route::post('customer/register', [CustomerController::class, 'register']);
Route::post('customer/login', [CustomerController::class, 'login']);
Route::middleware(['auth:sanctum', 'abilities:customer'])->post('customer/logout', [CustomerController::class, 'logout']);
Route::middleware(['auth:sanctum', 'abilities:customer'])->post('customer/self-update', [CustomerController::class, 'selfUpdate']);

// Authenticated Customer Routes For Admin & Dispatcher
Route::middleware(['auth:sanctum', 'user.only'])->get('customers', [CustomerController::class, 'index']);
Route::middleware(['auth:sanctum', 'user.only'])->post('customers', [CustomerController::class, 'store']);
Route::middleware(['auth:sanctum', 'user.only'])->get('customers/{id}', [CustomerController::class, 'show']);
Route::middleware(['auth:sanctum', 'user.only'])->post('customers/update/{id}', [CustomerController::class, 'update']);
Route::middleware(['auth:sanctum', 'user.only'])->delete('customers/{id}', [CustomerController::class, 'destroy']);

Route::apiResource('bookings', BookingController::class);

Route::apiResource('formtemplates', FormtemplateController::class);

Route::apiResource('formsubmissions', FormsubmissionController::class);

Route::apiResource('affiliates', AffiliateController::class);

Route::apiResource('affiliateclicks', AffiliateclickController::class);

Route::apiResource('vehicle_classes', VehicleClassController::class);
