<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageQoute extends Model
{
    use HasFactory;
    protected $fillable = [
        'package_name',
        'price',
        'flight_booking',
        'departure_date',
        'number_of_travelers',
        'full_name',
        'phone_number',
        'email_address',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'flight_booking' => 'boolean',
        'departure_date' => 'date',
    ];
}
