<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotDeal extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'deal_includes',
        'price',
        'other_info',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'price' => 'float',
        'other_info' => 'array'
    ];
}
