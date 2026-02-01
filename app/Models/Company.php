<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'email', 
        'phone', 
        'address', 
        'timezone', 
        'created_at', 
        'updated_at',
        // 'user_id',
        'logo',
        'url'
        ];

        public function user()
        {
            return $this->belongsTo(User::class);
        }
}