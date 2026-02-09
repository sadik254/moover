<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // return view('welcome');
    return response()->json(['message' => 'Welcome to the Taxi Booking API']);
});
