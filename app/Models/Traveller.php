<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Traveller extends Model
{
    use HasFactory;
    protected $table = 'travellers';
    protected $primaryKey = 'access_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    
    protected $fillable = [
        'usercode',
        'access_code',
        'travel_group',
        'title',
        'firstname',
        'surname',
        'othernames',
        'gender',
        'mobile_number',
        'mobile_number2',
        'email_address',
        'contact_address',
        'contact_city',
        'contact_state',
        'contact_country',
        'passport_country',
        'passport_number',
        'passport_expiry_date',
        'birth_date',
        'nationality',
        'passport_issue_date',
        'passport_file',
        'title'
    ];
}
