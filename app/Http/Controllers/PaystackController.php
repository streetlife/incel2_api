<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\InvoiceServices;
use App\Services\PaystackOrderServices;
use App\Services\PaystackService;
use App\Services\PaystackServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackController extends Controller
{
    protected $paystackService;

    public function __construct(PaystackServices $paystackService, protected PaystackOrderServices $PaystackOrderServices, protected InvoiceServices $invoiceServices)
    {
        $this->paystackService = $paystackService;
    }

    public function initialize(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'invoice_code' => 'required|string',
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'callback_url' => 'required|string'
        ]);

        $payment = $this->paystackService->initializePayment($request->all(),$request->callback_url);

        if (!$payment['status']) {
            Log::info($payment);
            return response()->json([
                'status' => false,
                'message' => $payment['message']
            ], 400);
        }

        $this->invoiceServices->updateTransactionReference(
            $request->invoice_code,
            $payment['transaction_reference']
        );

        // $this->PaystackOrderServices->create(
        //     $request->invoice_code,
        //     $payment['amount'],
        //     $payment['transaction_reference'],
        //     $payment['access_code'],
        //     $payment['reference']
        // );

        return response()->json([
            'status' => true,
            'payment_link' => $payment['authorization_url'],
            'reference' => $payment['reference']
        ]);
    }
}
