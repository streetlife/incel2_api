<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingTour extends Model
{
    use HasFactory;
    protected $table = 'bookings_tours';

    public $timestamps = false;
    
    protected $fillable = [
        'booking_code',
        'booking_detail_code',
        'traveller_code',
        'tour_id',
        'travel_date',
        'currency_code',
        'amount',
        'amount_display',
        'status',
        'transfer_id',
        'transfer_option',
        'transfer_name',
        'contract_id',
        'tour_option_id',
        'traveller_type',
        'time_slot_id',
        'time_slot_name'
    ];
}
