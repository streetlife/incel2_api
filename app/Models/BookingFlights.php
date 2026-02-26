<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\Cast;

class BookingFlights extends Model
{
    use HasFactory;

    protected $table = 'bookings_flights';
    public $timestamps = false;
    protected $fillable = [
        'booking_code',
        'booking_detail_code',
        'traveller_id',
        'traveller_type',
        'fare_option',
        'price',
        'price_markup',
        'flight_session',
        'payload',
        'amadeus_client_ref',
        'firstname',
        'surname',
        'birth_date',
        'passport_expiry_date',
        'passport_nationality',
        'phone_number',
        'passport_issuance_date'
    ];
    protected $cast = [
        'flight_session' => 'array',
        'payload' => 'array',
    ];
}
