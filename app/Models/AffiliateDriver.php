<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateDriver extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'name',
        'email',
        'phone',
        'status',
        'license_number',
        'license_expiry',
        'employment_type',
        'commission',
        'address',
        'photo',
        'notes',
    ];

    protected $casts = [
        'license_expiry' => 'date',
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }
}
