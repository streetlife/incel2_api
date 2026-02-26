<?php
namespace App\Services;

use App\Models\PaystackPayment;

class PaystackOrderServices {

    public function create(
        string $invoiceCode,
        int $amount,
        string $transactionReference,
        string $accessCode,
        string $reference
    ): bool {

        PaystackPayment::create([
            'invoice_code' => $invoiceCode,
            'amount' => $amount,
            'transaction_reference' => $transactionReference,
            'access_code' => $accessCode,
            'reference' => $reference
        ]);

        return true;
    }
}