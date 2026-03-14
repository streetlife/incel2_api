<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourGuide extends Model
{
    use HasFactory;
    protected $fillable = [
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'destination',
        'preferred_language',
        'date',
        'duration',
        'group_size',
        'tour_interest',
        'additional_info',
        'booking_code'
    ];

    protected $casts = [
        'tour_interest'=>'array'
    ];
}
