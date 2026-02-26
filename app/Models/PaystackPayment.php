<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaystackPayment extends Model
{
    use HasFactory;
      protected $table = 'payment_gateway_paystack';

    protected $fillable = [
        'invoice_code',
        'amount',
        'transaction_reference',
        'access_code',
        'reference'
    ];
}
