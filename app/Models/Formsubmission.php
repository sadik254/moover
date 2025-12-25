<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formsubmission extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'form_id', 'data_json'];
}