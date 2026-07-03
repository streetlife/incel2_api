<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CurrencyServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CurrencyController extends Controller
{
    //
    protected CurrencyServices $currencyService;

    public function __construct(CurrencyServices $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => $this->currencyService->getCurrencies()
        ]);
    }

    public function convert(Request $request)
    {
        $request->validate([
            'from' => 'required|string',
            'to' => 'required|string',
            'amount' => 'nullable|numeric'
        ]);

        $converted = $this->currencyService->convert(
            $request->from,
            $request->to,
            $request->amount
        );

        return response()->json([
            'status' => true,
            'from' => $request->from,
            'to' => $request->to,
            'original_amount' => $request->amount ?? 0,
            'converted_amount' => $converted,
            'formatted' => $this->currencyService->convertFormatted(
                $request->from,
                $request->to,
                $request->amount
            )
        ]);
    }
    public function getRates(Request $request)
    {
        $request->validate([
            'from' => 'required|string|size:3',
            'to'   => 'required|string|size:3',
        ]);

        try {
            $rate = $this->currencyService->getRate(
                $request->input('from'),
                $request->input('to')
            );

            if ($rate === null) {
                return response()->json([
                    'status' => false,
                    'message' => 'Exchange rate not found'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Rate fetched successfully',
                'data' => [
                    'rate' => $rate
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching the exchange rate',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function fetchAllRates()
    {
        try {
            $rates = $this->currencyService->fetchAllRates();

            return response()->json([
                'status' => true,
                'message' => 'Rates fetched successfully',
                'data' => $rates,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function fetchAllRatesById($id)
    {
        try {
            $rate = $this->currencyService->fetchAllRatesById($id);

            if (!$rate) {
                return response()->json([
                    'status' => false,
                    'message' => 'Rate not found',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Rate fetched successfully',
                'data' => $rate,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateRates(Request $request, $id)
    {
        try {
            $rate = $this->currencyService->updateRates(
                $id,
                $request->only([
                    'currency_from',
                    'currency_to',
                    'conversion_rate',
                    'conversion_date'
                ])
            );

            return response()->json([
                'status' => true,
                'message' => 'Rate updated successfully',
                'data' => $rate
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }
}
