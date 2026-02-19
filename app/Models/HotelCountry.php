<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelCountry extends Model
{
    use HasFactory;
    protected $table = 'hotels_countries';
    public $timestamps = false;
}
