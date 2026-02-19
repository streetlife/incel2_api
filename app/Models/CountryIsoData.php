<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryIsoData extends Model
{
    use HasFactory;
    protected $table = 'countries_iso_data';
    public $timestamps = false;
   
}
