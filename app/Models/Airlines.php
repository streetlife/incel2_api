<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Airlines extends Model
{
    use HasFactory;
    protected $table = 'airlines';

    protected $fillable = ['iataCode', 'airline'];
    public $timestamps = false;
    public function getLogoAttribute()
    {
        return config('app.image_base_url') . '/airlines/' . $this->iataCode . '.png';
    }
}
