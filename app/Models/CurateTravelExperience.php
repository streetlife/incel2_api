<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurateTravelExperience extends Model
{
    use HasFactory;
    protected $fillable = [
        'country',
        'number_of_adults',
        'number_of_kids',
        'kids_ages',
        'hotel_category',
        'flight_booking',
        'airport_transfer',
        'tour_and_activities',
        'special_request',
        'full_name',
        'phone_number',
        'email_address',
    ];

    protected $casts = [
        'kids_ages' => 'array',
        'flight_booking' => 'boolean',
        'airport_transfer' => 'boolean',
        'tour_and_activities' => 'boolean',
    ];
}
