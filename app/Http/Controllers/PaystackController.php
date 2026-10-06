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

            audit_log(
                'payment', 'init_failed', 'payment.paystack_init_failed',
                'Paystack payment initialization failed',
                metadata: ['amount' => $request->amount, 'invoice_code' => $request->invoice_code],
                status: 'failure',
            );

            return response()->json([
                'status' => false,
                'message' => $payment['message']
            ], 400);
        }

        $this->invoiceServices->updateTransactionReference(
            $request->invoice_code,
            $payment['transaction_reference']
        );

        audit_log(
            'payment', 'initialized', 'payment.paystack',
            'Paystack payment initialized',
            metadata: ['amount' => $request->amount, 'invoice_code' => $request->invoice_code, 'reference' => $payment['reference']],
            status: 'success',
        );

        return response()->json([
            'status' => true,
            'payment_link' => $payment['authorization_url'],
            'reference' => $payment['reference']
        ]);
    }
}
