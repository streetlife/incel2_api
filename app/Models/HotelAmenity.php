<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelAmenity extends Model
{
    use HasFactory;
    protected $table = 'hotels_amenities';
    public $timestamps = false;

    protected $fillable = [
        'hotel_code',
        'amenities'
    ];
}
