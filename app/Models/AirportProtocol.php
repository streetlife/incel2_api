<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AirportProtocol extends Model
{
    use HasFactory;
    protected $fillable = [
        'service_type',
        'airport_name',
        'flight_number',
        'service_required',
        'additional_info',
        'booking_code',
        'airline',
        'number_of_passengers',
    ];
    protected $casts = [
        'service_required' => 'array'
    ];
}
