<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AirportTransfer extends Model
{
    use HasFactory;
    protected $table = "bookings_services";
    public $timestamps = false;
    protected $fillable = [
        'usercode',
        'booking_code',
        'service',
        'request_details',
        'status',
        'date_created',
        'date_updated',
    ];
    protected $casts = [
        'request_details' => 'array'
    ];
}
