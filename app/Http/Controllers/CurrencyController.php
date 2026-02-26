<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CurrencyServices;
use Illuminate\Http\Request;

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
}
