<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Affiliateclick extends Model
{
    use HasFactory;

    protected $fillable = ['affiliate_id', 'ip_address', 'user_agent'];
}