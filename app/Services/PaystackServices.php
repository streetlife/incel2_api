<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaystackServices
{
    public function initializePayment(array $data,string $callbackUrl)
    {
        $transactionReference = Str::uuid()->toString();
        $amount = $data['amount'] * 100; // convert to kobo

        $redirectUrl = $callbackUrl ;

        $response = Http::withToken(config('paystack.secret_key'))
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $data['customer_email'],
                'amount' => $amount,
                'currency' => 'NGN',
                'callback_url' => $redirectUrl,
                'metadata' => [
                    'invoice_code' => $data['invoice_code'],
                    'customer_name' => $data['customer_name'],
                    'transaction_reference' => $transactionReference
                ]
            ]);

        if (!$response->successful()) {

            Log::error('Paystack HTTP Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'status' => false,
                'message' => 'Paystack request failed'
            ];
        }

        if (!$response['status']) {

            Log::error('Paystack API Rejection', [
                'response' => $response->json()
            ]);

            return [
                'status' => false,
                'message' => $response['message'] ?? 'Unable to initialize payment'
            ];
        }

        return [
            'status' => true,
            'authorization_url' => $response['data']['authorization_url'],
            'reference' => $response['data']['reference'],
            'access_code' => $response['data']['access_code'],
            'transaction_reference' => $transactionReference,
            'amount' => $amount
        ];
    }
     public function initializePaymentforVisa(array $data,string $callbackUrl)
    {
        $transactionReference = Str::uuid()->toString();
        $amount = $data['amount'] * 100; // convert to kobo

        $redirectUrl = $callbackUrl ;

        $response = Http::withToken(config('paystack.secret_key'))
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $data['customer_email'],
                'amount' => $amount,
                'currency' => 'NGN',
                'callback_url' => $redirectUrl,
                'metadata' => [
                    'booking_code' => $data['booking_code'],
                    'customer_name' => $data['customer_name'],
                    'transaction_reference' => $transactionReference
                ]
            ]);

        if (!$response->successful()) {

            Log::error('Paystack HTTP Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'status' => false,
                'message' => 'Paystack request failed'
            ];
        }

        if (!$response['status']) {

            Log::error('Paystack API Rejection', [
                'response' => $response->json()
            ]);

            return [
                'status' => false,
                'message' => $response['message'] ?? 'Unable to initialize payment'
            ];
        }

        return [
            'status' => true,
            'authorization_url' => $response['data']['authorization_url'],
            'reference' => $response['data']['reference'],
            'access_code' => $response['data']['access_code'],
            'transaction_reference' => $transactionReference,
            'amount' => $amount
        ];
    }
}
