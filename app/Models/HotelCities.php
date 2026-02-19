<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelCities extends Model
{
    use HasFactory;
    protected $table = 'hotels_cities';
    public $timestamps = false;
}
