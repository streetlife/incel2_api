<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    protected $table = 'bookings';
    public $timestamps = false;
    protected $fillable = [
        'usercode',
        'booking_code',
        'booking_type',
        'booking_status',
        'date_expiry'
    ];

    public function tours()
    {
        return $this->hasMany(BookingTour::class, 'booking_code', 'booking_code');
    }

    public function flights()
    {
        return $this->hasMany(BookingFlights::class, 'booking_code', 'booking_code');
    }

    public function hotels()
    {
        return $this->hasMany(BookingHotel::class, 'booking_code', 'booking_code');
    }

    public function visas()
    {
        return $this->hasMany(BookingVisa::class, 'booking_code', 'booking_code');
    }
}
