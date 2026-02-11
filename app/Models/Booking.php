<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Vehicle;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'customer_id',
        'name',
        'email',
        'phone',
        'vehicle_id',
        'driver_id',
        'service_type',
        'pickup_address',
        'dropoff_address',
        'pickup_time',
        'dropoff_time',
        'passengers',
        'child_seats',
        'bags',
        'flight_number',
        'airlines',
        'distance_km',
        'hours',
        'base_price',
        'extras_price',
        'total_price',
        'final_price',
        'final_price',
        'taxes',
        'gratuity',
        'parking',
        'others',
        'payment_method',
        'payment_status',
        'status',
        'notes',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
