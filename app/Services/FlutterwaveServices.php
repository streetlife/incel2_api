<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FlutterwaveServices
{
    public function initializePayment(array $data)
    {
        $transactionReference = 'txn_' . Str::random(12);

        $redirectUrl = 'https://incel2-nine.vercel.app/'.'/api/payment/flutterwave/callback';

        $payload = [
            'tx_ref' => $transactionReference,
            'amount' => $data['amount'],
            'currency' => 'NGN',
            'redirect_url' => $redirectUrl,
            'payment_options' => 'card',
            'customer' => [
                'email' => $data['customer_email'],
                'phonenumber' => '',
                'name' => $data['customer_name']
            ],
            'meta' => [
                'consumer_mac' => $data['invoice_code']
            ],
            'customizations' => [
                'title' => 'Incel Portal Payments',
                'description' => 'Low cost options for travels.',
                'logo' => 'https://www.inceltourism.com/wp-content/uploads/2017/09/incellogo.jpg'
            ]
        ];

        $response = Http::withToken(config('flutterwave.secret_key'))
            ->post(config('flutterwave.payment_url'), $payload);

        if (!$response->successful()) {
            Log::error('Flutterwave HTTP Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'status' => false,
                'message' => 'Flutterwave request failed'
            ];
        }

        $body = $response->json();

        if ($body['status'] !== 'success') {
            Log::error('Flutterwave API Rejection', $body);

            return [
                'status' => false,
                'message' => $body['message'] ?? 'Unable to initialize payment'
            ];
        }

        return [
            'status' => true,
            'payment_link' => $body['data']['link'],
            'transaction_reference' => $transactionReference
        ];
    }
}