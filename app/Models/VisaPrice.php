<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisaPrice extends Model
{
    use HasFactory;

    protected $table = 'visa_prices';
    public $timestamps = false;
}
