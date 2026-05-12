<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\TourServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TourController extends Controller
{

    public function __construct(public TourServices $tourService) {}

    // public function search(Request $request)
    // {
    //     $request->validate([
    //         'country_id' => 'required|integer',
    //         'city_id'    => 'required|integer',
    //         'date'       => 'required|date',
    //     ]);

    //     $result = $this->tourService->search(
    //         $request->country_id,
    //         $request->city_id,
    //         $request->date
    //     );
    //     if (!$result) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => "Something went wrong"
    //         ]);
    //     }
    //     return response()->json($result['data']);
    // }
    public function search(Request $request)
    {
        $request->validate([
            'country_id' => 'required|integer',
            'city_id'    => 'required|integer',
            'date'       => 'required|date',
        ]);

        $result = $this->tourService->search(
            $request->integer('country_id'),
            $request->integer('city_id'),
            $request->input('date'),
        );

        if (!$result['status']) {
            return response()->json([
                'status'  => false,
                'message' => $result['message'] ?? 'Search failed',
                'data'    => null,
            ], 400);
        }

        return response()->json([
            'status'       => true,
            'message'      => $result['message']      ?? 'Tours fetched successfully',
            'session_code' => $result['session_code'] ?? null,
            'data'         => $result['data']         ?? null,
        ], 200);
    }
    public function getTourCountries()
    {
        try {
            $result = $this->tourService->getTourCountries();
            return response()->json($result);
        } catch (\Throwable $th) {
            Log::info("error", [$th->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => "Something went wrong"
            ]);
        }
    }
    public function getTourCities(mixed $countryId)
    {
        try {
            $result = $this->tourService->getTourCities($countryId);
            return response()->json($result);
        } catch (\Throwable $th) {
            Log::info("error", [$th->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => "Something went wrong"
            ]);
        }
    }
}
