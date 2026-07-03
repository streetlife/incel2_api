<?php

namespace App\Services;

use App\Models\CurrencyRates;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function getRate(string $from, string $to)
    {

        if ($from === $to) {
            return 1.0;
        }
        $rate = CurrencyRates::where('currency_from', strtoupper($from))
            ->where('currency_to', strtoupper($to))
            ->orderByDesc('conversion_date')
            ->value('conversion_rate');

        Log::info("rate", ["rate"=>$rate]);

        if ($rate === null) {
            throw new \Exception(
                "Exchange rate not found for {$from} to {$to}"
            );
        }

        return  $rate;
    }

    public function fetchAllRates()
    {
        return CurrencyRates::all();
    }
    public function fetchAllRatesById($id)
    {
        return CurrencyRates::where("id", "=", $id)->get();
    }

    public function updateRates($id, array $data)
    {
        $rate = CurrencyRates::findOrFail($id);

        $rate->update($data);

        return $rate->fresh();
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
