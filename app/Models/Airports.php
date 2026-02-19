<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Airports extends Model
{
    use HasFactory;

    protected $table = 'airports';
    protected $fillable = [
        'airport_code',
        'airport_name',
        'country_iso_code',
        'enabled',
        'name',
        'cityname',
        'countryCode'
    ];
    public $timestamps = false;
    
    
}
