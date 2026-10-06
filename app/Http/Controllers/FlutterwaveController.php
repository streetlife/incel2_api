<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\FlutterwaveServices;
use App\Services\InvoiceServices;
use Illuminate\Http\Request;

class FlutterwaveController extends Controller
{
    protected $flutterwaveService;
    protected $invoiceService;

    public function __construct(
        FlutterwaveServices $flutterwaveService,
        InvoiceServices $invoiceService
    ) {
        $this->flutterwaveService = $flutterwaveService;
        $this->invoiceService = $invoiceService;
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

        $payment = $this->flutterwaveService->initializePayment($request->all(), $request->callback_url);

        if (!$payment['status']) {
            audit_log(
                'payment', 'init_failed', 'payment.flutterwave_init_failed',
                'Flutterwave payment initialization failed',
                metadata: ['amount' => $request->amount, 'invoice_code' => $request->invoice_code],
                status: 'failure',
            );

            return response()->json($payment, 400);
        }


        $this->invoiceService->updateTransactionReference(
            $request->invoice_code,
            $payment['transaction_reference']
        );

        audit_log(
            'payment', 'initialized', 'payment.flutterwave',
            'Flutterwave payment initialized',
            metadata: ['amount' => $request->amount, 'invoice_code' => $request->invoice_code, 'reference' => $payment['transaction_reference'] ?? null],
            status: 'success',
        );

        return response()->json([
            'status' => true,
            'payment_link' => $payment['payment_link']
        ]);
    }

    public function invoice(Request $request)
    {
        $data = $this->invoiceService->createInvoice($request->all());
        if (!$data) {
            audit_log('invoice', 'creation_failed', 'invoice.creation_failed', 'Invoice creation failed', status: 'failure');

            return response()->json([
                'status' => false,
                'message' => 'Failed to create invoice'
            ], 400);
        }

        audit_log('invoice', 'created', 'invoice.created', 'Invoice created', metadata: $request->only(['amount', 'description']), status: 'success');

        return response()->json([
            'status' => true,
            'invoice' => $data
        ], 200);
    }
}
