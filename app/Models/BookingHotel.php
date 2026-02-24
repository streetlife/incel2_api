<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingHotel extends Model
{
    use HasFactory;

    protected $table = 'bookings_hotels';
    public $timestamps = false;

    protected $fillable = [
        'booking_code',
        'booking_detail_code',
        'hotel_id',
        'date_from',
        'date_to',
        'currency_code',
        'amount',
        'amount_display',
        'booking_key',
        'traveller_title',
        'first_name',
        'last_name',
        'country_code',
        'city_code',
        'room_type',
        'nationality',
        'session_id',
        'adults',
        'children',
        'totalRooms',
        'totalRates',
    ];
    
}
