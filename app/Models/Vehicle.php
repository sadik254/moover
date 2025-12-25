<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'name', 'category', 'capacity', 'luggage', 'hourly_rate', 'per_km_rate', 'airport_rate'];
}