<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisaSession extends Model
{
    use HasFactory;
    protected $table = 'visa_sessions';
    public $timestamps = false;
}
