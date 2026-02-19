<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // return view('welcome');
    return response()->json(['message' => 'Welcome to the Taxi Booking API']);
});

Route::get('/maintenance/clear', function (Request $request) {
    // $expectedKey = (string) env('MAINTENANCE_KEY', '');
    // $providedKey = (string) $request->query('key', '');

    // if ($expectedKey === '' || ! hash_equals($expectedKey, $providedKey)) {
    //     return response()->json(['message' => 'Unauthorized'], 403);
    // }

    Artisan::call('optimize:clear');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');

    return response()->json([
        'message' => 'Maintenance clear commands executed successfully',
    ]);
});