<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'customer_id', 'vehicle_id', 'driver_id', 'service_type', ''hourly'', ''airport'', ''custom')', 'pickup_address', 'dropoff_address', 'pickup_time', 'passengers', 'distance_km', 'base_price', 'extras_price', 'total_price', 'status', ''confirmed'', ''assigned'', ''on_route'', ''completed'', ''cancelled')', 'notes'];
}