<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrencyRates extends Model
{
    use HasFactory;
    protected $table = 'currency_rates';
    public $timestamps = false;
    protected $fillable = [
        "currency_from",
        "currency_to",
        "conversion_rate",
        "conversion_date"
    ];
}
