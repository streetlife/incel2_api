<?php 
namespace App\Services;

use App\Models\Invoice;

class InvoiceServices {
     public function updateTransactionReference(string $invoiceCode, string $transactionReference): bool
    {
        $invoice = Invoice::where('invoice_code', $invoiceCode)->first();

        if (!$invoice) {
            return false;
        }

        $invoice->transaction_reference = $transactionReference;
        $invoice->save();

        return true;
    }
}