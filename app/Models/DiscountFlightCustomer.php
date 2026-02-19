<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountFlightCustomer extends Model
{
    use HasFactory;
    protected $table = 'discounts_flights_customers';
     public $timestamps = false;
}
