<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoTestimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'country',
        'comment',
        'rating',
        'review',
        'video_url',
        'public_id',
    ];
}
