<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionTour extends Model
{
    use HasFactory;
    protected $table = 'sessions_tours';
    public $timestamps = false;
    protected $fillable = [
        'session_code',
        'country_id',
        'city_id',
        'travel_date',
        'currency',
        'currency_symbol',
        'result_count',
    ];

    protected $casts = [
        'travel_date' => 'date',
        'result_count' => 'integer',
    ];
}
