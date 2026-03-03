<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisaPassportType extends Model
{
    use HasFactory;
    protected $table = 'visa_passporttypes';
    public $timestamps = false;
}
