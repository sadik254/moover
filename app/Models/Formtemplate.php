<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formtemplate extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'name', 'schema_json'];
}