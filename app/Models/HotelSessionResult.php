<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelSessionResult extends Model
{
    use HasFactory;
    protected $table = 'sessions_hotels_results';
    public $timestamps = false;
    protected $guarded = [];
    
    protected $fillable = [
        'session_code',
        'hotel_id',
        'hotel_rating',
        'hotel_thumbs',
        'price',
        'room_count',
        'amenities'
    ];
    protected $casts = [
        'amenities' => 'array'
    ];
}
