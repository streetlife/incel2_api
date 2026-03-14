<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AboutUs extends Model
{
    use HasFactory;
    protected $fillable = [
        'banner_title',
        'banner_image',
        'banner_description',
        'story',
        'story_image',
        'our_promise',
        'core_value',
        'our_mission'
    ];

    protected $casts = [
        'story' => 'array',
        'our_promise' => 'array',
        'core_value' => 'array',
        'our_mission' => 'array'
    ];
}
