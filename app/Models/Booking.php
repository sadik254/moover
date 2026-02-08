<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'customer_id',
        'vehicle_id',
        'driver_id',
        'service_type',
        'pickup_address',
        'dropoff_address',
        'pickup_time',
        'passengers',
        'child_seats',
        'bags',
        'flight_number',
        'airlines',
        'distance_km',
        'base_price',
        'extras_price',
        'total_price',
        'taxes',
        'gratuity',
        'parking',
        'others',
        'payment_method',
        'payment_status',
        'status',
        'notes',
    ];
}
