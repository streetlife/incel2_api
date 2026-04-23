<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    protected $table = 'invoices';
    public $timestamps = false;
    protected $fillable = [
        'invoice_code',
        'usercode',
        'invoice_date',
        'invoice_due_date',
        'amount_total',
        'amount_paid',
        'invoice_status',
        'invoice_comments',
        'currency_code',
        'currency_code_display',
        'exchange_rate',
        'amount_markup_aed',
        'amount_markup_usd',
        'amount_markup_ngn'
    ];
}
