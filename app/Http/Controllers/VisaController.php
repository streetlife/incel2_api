<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\FlutterwaveServices;
use App\Services\PaystackServices;
use App\Services\VisaServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VisaController extends Controller
{
    protected $visaService;

    public function __construct(VisaServices $visaService, protected FlutterwaveServices $flutterwaveService, protected PaystackServices $paystack)
    {
        $this->visaService = $visaService;
    }
    public function getMetadata()
    {
        $data = $this->visaService->getVisaMetadata();
        if (!$data) {
            return response()->json(['status' => false, 'message' => 'error', 'data' => $data], 400);
        }
        return response()->json(['status' => true, 'message' => 'successfull', 'data' => $data], 200);
    }
    public function showSession($code)
    {
        $session = $this->visaService->getVisaSession($code);

        if ($session->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Session not found'], 404);
        }

        return response()->json(['status' => true, 'message' => 'sucessfull', 'data' => $session], 200);
    }
    public function visaById($id)
    {
        $visa = $this->visaService->getVisa($id);
        if (!$visa) {
            return response()->json(['status' => false, 'message' => 'visa not found'], 404);
        }
        return response()->json(['status' => true, 'message' => 'successfull', 'data' => $visa], 200);
    }
    public function search(Request $request)
    {
        $validated = $request->validate([
            'country_destination' => 'required|string',
            'country_nationality' => 'required|string',
            'adult_number'        => 'nullable|integer|min:1',
        ]);

        try {
            $sessionCode = $this->visaService->createVisaSearchSession($validated);

            return response()->json([
                'status' => true,
                'message' => 'Search session created successfully',
                'session_code' => $sessionCode,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error creating search session: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function getSession(string $session_code)
    {
        if (empty($session_code)) {
            return response()->json([
                'status' => false,
                'message' => 'Session code is required'
            ], 400);
        }

        $response = $this->visaService->getVisaBySession($session_code);

        return response()->json(
            [
                'status' => $response['status'],
                'message' => $response['message'],
                'data' => $response['data'] ?? null
            ],
            $response['code']
        );
    }
    public function payment(Request $request)
{
    try {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'booking_code' => 'required|string',
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'callback_url' => 'required|string',
            'payment_type' => 'required|string',
        ]);

        $paymentData = [
            'amount' => $validated['amount'],
            'booking_code' => $validated['booking_code'],
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
        ];

        $getPaymentType = match ($validated['payment_type']) {
            'flutterwave' => $this->flutterwaveService->initializePaymentForVisa($paymentData, $validated['callback_url']),
            'paystack' => $this->paystack->initializePaymentforVisa($paymentData, $validated['callback_url']),
            default => null
        };

        $link = $validated['payment_type'] === 'flutterwave'
            ? $getPaymentType['payment_link']
            : $getPaymentType['authorization_url'];

        return response()->json([
            'status' => true,
            'message' => 'Successful',
            'data' => [
                'link' => $link
            ]
        ], 200);

    } catch (\Exception $e) {
        Log::error('Payment initialization failed: ' . $e->getMessage());

        return response()->json([
            "status" => false,
            "message" => 'Payment failed',
            "data" => null
        ], 400);
    }
}
}
