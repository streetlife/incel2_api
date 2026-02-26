<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CurrencyServices
{
    public function getCurrencies(): array
    {
        return DB::table('currency_codes')
            ->get()
            ->toArray();
    }

    
    public function convert(string $from, string $to, ?float $amount): float
    {
        $amount = $amount ?? 0;

        if ($from === $to) {
            return $amount;
        }

        $rate = $this->getRate($from, $to);

        return $rate * $amount;
    }

    public function convertFormatted(string $from, string $to, ?float $amount): string
    {
        $converted = $this->convert($from, $to, $amount);

        return $to . ' ' . number_format($converted, 2);
    }

  
    public function getRate(string $from, string $to): float
    {
        if ($from === $to) {
            return 1;
        }

        $rate = DB::table('currency_rates')
            ->where('currency_from', $from)
            ->where('currency_to', $to)
            ->orderByDesc('conversion_date')
            ->value('conversion_rate');

        return $rate ?? 1;
    }

 
    public function getMarkup(string $module, string $customerType = 'B2C'): ?object
    {
        return DB::table('markups')
            ->where('module', $module)
            ->where('customer_type', $customerType)
            ->first();
    }

    public function applyMarkup(float $amount, ?object $markup): float
    {
        if (!$markup || $amount <= 0) {
            return $amount;
        }

        if ($markup->markup_type === 'FLAT') {
            return $amount + $markup->markup_amount;
        }

        if ($markup->markup_type === 'PERCENTAGE') {
            return $amount + (($markup->markup_amount / 100) * $amount);
        }

        return $amount;
    }
}