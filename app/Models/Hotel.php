<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasFactory;
    protected $table = 'hotels';
    protected $primaryKey = 'hotel_code';
    public $incrementing = false;
    public $timestamps = false;
}
