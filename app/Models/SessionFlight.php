<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionFlight extends Model
{
    use HasFactory;
    protected $table = 'sessions_flights';
    public $timestamps = false;
    protected $fillable = [
        'session_code',
        'amadeus_client_ref',
        'search_type',
        'payload',
        'response',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
    ];
}
