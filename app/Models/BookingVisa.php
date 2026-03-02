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
        'surname',
        'firstname',
        'othernames',
        'passport_expiry_date',
        'passport_country',
        'passport_number',
        'passport_issuance_date',
        'emailaddress',
        'birth_date',
        'document_data_page',
        'document_passport_photo',
        'status'
    ];
}
