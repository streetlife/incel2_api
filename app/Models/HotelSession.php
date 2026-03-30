<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelSession extends Model
{
    use HasFactory;
    protected $table = 'sessions_hotels';
    protected $guarded = [];
    public $timestamps = false;
    protected $fillable = [
        'session_code',
        'country_code',
        'city_code',
        'arrival_date',
        'departure_date',
        'currency',
        'currency_code',
        'result_count',
        'rooms',
        'adults',
        'children',
        'nationality',
        'search_session_id'
    ];
}
