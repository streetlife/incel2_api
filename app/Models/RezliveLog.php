<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RezliveLog extends Model
{
    use HasFactory;
     protected $fillable = [
        'type',
        'request_xml',
        'response_xml',
        'request_payload',
        'status_code',
    ];

    protected $casts = [
        'request_payload' => 'array',
    ];
}
