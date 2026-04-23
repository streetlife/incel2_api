<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Markup;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class InvoiceServices
{
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

    public function createInvoice(array $data)
    {
        try {
            $booking_code = $data['booking_code'] ?? null;
            $currency_base = $data['currency_base'] ?? 'AED';
            $amount_total = $data['amount_total'] ?? 0;
            $amount_paid = $data['amount_paid'] ?? 0;
            $module = $data['module'];
            if (!$booking_code) {
                return [
                    'status' => false,
                    'message' => 'booking_code are required'
                ];
            }

            $invoice_code = $this->uniqueReference('I');

            $currency_display = $currency_base;
            $auth = Auth::user();
            $usercode = $auth->usercode ?? 'temp' . now()->format('ymdHis');

            $accessLevels = $auth->access_level ?? null;
            $customer = in_array($accessLevels, ['admin', 'superadmin']) ? 'B2B' : 'B2C';
            $getMark = Markup::where('customer_type', $customer)->where('module', $module)
                ->where('currency_code', $currency_base)->first();

            if (!$getMark) {
                return ['status' => false, 'message' => 'No markup configuration found for this module and currency'];
            }
            $amount_markup_aed = $currency_base === "AED" ? $getMark->amount_markup : 0;
            $amount_markup_usd =  $currency_base === "USD" ? $getMark->amount_markup : 0;
            $amount_markup_ngn = $currency_base === "NGN" ? $getMark->amount_markup : 0;

            $invoice = Invoice::create([
                'invoice_code' => $booking_code,
                'usercode' => $usercode,
                'booking_code' => $booking_code,
                'invoice_date' => Carbon::now(),
                'invoice_due_date' => Carbon::now()->addHours(5),
                'amount_total' => $amount_total,
                'amount_paid' => $amount_paid,
                'invoice_status' => 'NEW',
                'invoice_comments' => '',
                'currency_code' => $currency_base,
                'currency_code_display' => $currency_display,
                'exchange_rate' => 0,
                'amount_markup_aed' => $amount_markup_aed ?? 0,
                'amount_markup_usd' => $amount_markup_usd ?? 0,
                'amount_markup_ngn' =>  $amount_markup_ngn ?? 0
            ]);

            return [
                'status' => true,
                'message' => 'Invoice created successfully',
                'data' => $invoice
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to create invoice',
                'error' => $e->getMessage()
            ];
        }
    }
    private function uniqueReference($prefix)
    {
        return $prefix . strtoupper(uniqid());
    }
}
