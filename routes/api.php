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
use App\Http\Controllers\SystemConfigController;
use App\Http\Controllers\BookingPaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Register and login routes for Users without authentication middleware
Route::post('user/register', [UserController::class, 'register']);
Route::post('user/login', [UserController::class, 'login']);
// Route::post('user/forgot-password', [UserController::class, 'forgotPassword']);

// Admin: create dispatcher
Route::middleware(['auth:sanctum', 'user.only:admin'])->post('user/create-dispatcher', [UserController::class, 'createDispatcher']);

// Update user profile route with authentication middleware
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->post('/user/update', [UserController::class, 'update']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->get('user', [UserController::class, 'show']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->post('/user/logout', [UserController::class, 'logout']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->post('/user/update-password', [UserController::class, 'updatePassword']);

// Authenticated Company Routes
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->post('/company', [CompanyController::class, 'store']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->get('/company', [CompanyController::class, 'index']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->post('/company/update', [CompanyController::class, 'update']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->delete('/company', [CompanyController::class, 'destroy']);

// System Config Routes
Route::get('/system-config', [SystemConfigController::class, 'index']);
Route::middleware(['auth:sanctum', 'user.only:admin'])->post('/system-config', [SystemConfigController::class, 'store']);
Route::middleware(['auth:sanctum', 'user.only:admin'])->post('/system-config/update', [SystemConfigController::class, 'update']);

// Authenticated Vehicle Class Routes
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->get('/vehicle-classes', [VehicleClassController::class, 'index']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->post('/vehicle-classes', [VehicleClassController::class, 'store']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->get('/vehicle-classes/{id}', [VehicleClassController::class, 'show']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->post('/vehicle-classes/update/{id}', [VehicleClassController::class, 'update']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->delete('/vehicle-classes/{id}', [VehicleClassController::class, 'destroy']);

// Authenticated Vehicle Routes
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->get('/vehicles', [VehicleController::class, 'index']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->post('/vehicles', [VehicleController::class, 'store']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->get('/vehicles/{id}', [VehicleController::class, 'show']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->post('/vehicles/update/{id}', [VehicleController::class, 'update']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->delete('/vehicles/{id}', [VehicleController::class, 'destroy']);

// Authenticated Driver Routes For Admin & Dispatcher
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->get('drivers', [DriverController::class, 'index']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->post('drivers', [DriverController::class, 'store']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->get('drivers/{id}', [DriverController::class, 'show']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->post('drivers/update/{id}', [DriverController::class, 'update']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->delete('drivers/{id}', [DriverController::class, 'destroy']);

// Customer auth routes (abilities-based)
Route::post('customer/register', [CustomerController::class, 'register']);
Route::post('customer/login', [CustomerController::class, 'login']);
Route::middleware(['auth:sanctum', 'abilities:customer'])->post('customer/logout', [CustomerController::class, 'logout']);
Route::middleware(['auth:sanctum', 'abilities:customer'])->post('customer/self-update', [CustomerController::class, 'selfUpdate']);

// Authenticated Customer Routes For Admin & Dispatcher
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->get('customers', [CustomerController::class, 'index']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->post('customers', [CustomerController::class, 'store']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->get('customers/{id}', [CustomerController::class, 'show']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->post('customers/update/{id}', [CustomerController::class, 'update']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->delete('customers/{id}', [CustomerController::class, 'destroy']);

// Booking routes
Route::post('bookings', [BookingController::class, 'store']); // public: quote + create
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->get('bookings', [BookingController::class, 'index']);
Route::middleware(['auth:sanctum', 'abilities:customer'])->get('customer/bookings', [BookingController::class, 'customerBookings']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->get('bookings/{id}', [BookingController::class, 'show']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->post('bookings/update/{id}', [BookingController::class, 'update']);
Route::middleware(['auth:sanctum', 'user.only:admin,dispatcher'])->delete('bookings/{id}', [BookingController::class, 'destroy']);

// Booking payment routes
Route::post('payments/webhook/stripe', [BookingPaymentController::class, 'webhook']);
Route::middleware(['auth:sanctum'])->get('bookings/{id}/payment', [BookingPaymentController::class, 'show']);
Route::post('bookings/{id}/payment/authorize', [BookingPaymentController::class, 'authorizePayment']);
Route::middleware(['auth:sanctum'])->post('bookings/{id}/payment/capture', [BookingPaymentController::class, 'capturePayment']);

Route::apiResource('formtemplates', FormtemplateController::class);

Route::apiResource('formsubmissions', FormsubmissionController::class);

Route::apiResource('affiliates', AffiliateController::class);

Route::apiResource('affiliateclicks', AffiliateclickController::class);
