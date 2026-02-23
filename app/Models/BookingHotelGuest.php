<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingHotelGuest extends Model
{
    use HasFactory;
    protected $table = 'bookings_hotels_guests';

    protected $fillable = [
        'guest_code',
        'booking_detail_code',
        'booking_code',
        'traveller_title',
        'first_name',
        'last_name',
        'guest_type',
    ];
    public $timestamps = false;

}
