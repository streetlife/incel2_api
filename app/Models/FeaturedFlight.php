<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeaturedFlight extends Model
{
    use HasFactory;

    protected $table = 'featured_flights';
     public $timestamps = false;
}
