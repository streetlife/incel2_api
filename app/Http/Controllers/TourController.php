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
    public function getTourPricing(Request $request)
    {
        $data = $request->validate([
            'tour_id'     => 'required|integer',
            'contract_id' => 'required|integer',
            'travel_date' => 'required|date',
        ]);

        $result = $this->tourService->getTourPricing($data);

        if (!$result['status']) {
            return response()->json([
                'status'  => false,
                'message' => $result['message'],
                'data'    => null,
            ], 400);
        }

        return response()->json([
            'status'  => true,
            'message' => $result['message'],
            'data'    => $result['data'],
        ], 200);
    }
    public function processBooking(Request $request)
    {
        $request->validate([
            'booking_code'              => 'required|string|',
            'booking_tours'             => 'required|array|min:1',
            'booking_tours.*.tour_id'           => 'required|integer',
            'booking_tours.*.tour_option_id'    => 'required|integer',
            'booking_tours.*.travel_date'       => 'required|date',
            'booking_tours.*.time_slot_id'      => 'nullable|integer',
            'booking_tours.*.transfer_id'       => 'required|integer',
            'booking_tours.*.traveller_type'    => 'required|in:ADULT,CHILD,INFANT',
            'booking_tours.*.amount'            => 'required|numeric',
            'booking_tours.*.firstname'         => 'required|string',
            'booking_tours.*.surname'           => 'required|string',
            'booking_tours.*.emailaddress'      => 'required|email',
            'booking_tours.*.phone_number'      => 'required|string',
            'booking_tours.*.passport_nationality' => 'required|string',
            'booking_tours.*.booking_detail_code'  => 'required|string',
        ]);

        $result = $this->tourService->processBooking(
            bookingCode:  $request->booking_code,
            bookingTours: $request->booking_tours,
        );

        if (!$result['status']) {
            return response()->json([
                'status'  => false,
                'message' => $result['message'],
                'data'    => null,
            ], 400);
        }

        return response()->json([
            'status'  => true,
            'message' => $result['message'],
            'data'    => $result['data'],
        ], 200);
    }
}
