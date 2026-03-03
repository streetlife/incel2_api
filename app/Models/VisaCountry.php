<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisaCountry extends Model
{
    use HasFactory;
    protected $table = 'visas_countries';
    public $timestamps = false;
}
