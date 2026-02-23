<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingVisa extends Model
{
    use HasFactory;

    protected $table = 'bookings_visas';
    public $timestamps = false;
    protected $fillable = [
        'booking_code',
        'booking_detail_code',
        'visa_id',
    ];
}
