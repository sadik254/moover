<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Driver extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'name',
        'email',
        'phone',
        'password',
        'password_reset_code',
        'password_reset_code_sent_at',
        'license_number',
        'license_expiry',
        'status',
        'employment_type',
        'commission',
        'license_front',
        'license_back',
        'address',
        'photo',
        'available',
    ];

    protected $hidden = [
        'password',
        'password_reset_code',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'password_reset_code_sent_at' => 'datetime',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
