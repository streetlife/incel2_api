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
            return response()->json($payment, 400);
        }


        $this->invoiceService->updateTransactionReference(
            $request->invoice_code,
            $payment['transaction_reference']
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
            return response()->json([
                'status' => false,
                'message' => 'Failed to create invoice'
            ], 400);
        }
        return response()->json([
            'status' => true,
            'invoice' => $data
        ], 200);
    }
}
