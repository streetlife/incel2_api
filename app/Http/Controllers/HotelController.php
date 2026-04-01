<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\HotelServices;
use App\Services\RezliveServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HotelController extends Controller
{
    protected HotelServices $hotelService;

    public function __construct(HotelServices $hotelService,protected RezliveServices $rezliveBooking)
    {
        $this->hotelService = $hotelService;
    }


    public function countries()
    {
        return response()->json(
            $this->hotelService->getCountries()
        );
    }

    public function cities(string $countryCode)
    {
        return response()->json(
            $this->hotelService->getCities($countryCode)
        );
    }

    public function show(string $hotelCode)
    {
        $hotel = $this->hotelService->getHotel($hotelCode);

        if (!$hotel) {
            return response()->json([
                'status' => false,
                'message' => 'Hotel not found'
            ], 404);
        }

        return response()->json($hotel);
    }


    public function search(Request $request)
    {
        // Log::info('Search request payload', $request->all());
        $data = $this->hotelService->search(
            $request->all(),
            $request->input('arrival_date'),
            $request->input('departure_date')
        );
        return $data;
    }


    public function amenities(string $hotelCode)
    {
        return response()->json(
            $this->hotelService->getHotelAmenities($hotelCode)
        );
    }
    public function hotelDetail($sessionCode, $hotelId)
    {
        try {

            $data = $this->hotelService->getHotelDetail($sessionCode, $hotelId);

            return response()->json($data);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
     public function bookHotel(Request $request)
    {
        $request->validate([
            'booking_code' => 'required',
            'hotels' => 'required|array'
        ]);

        $result = $this->rezliveBooking->processBooking(
            $request->booking_code,
            $request->hotels
        );

        return response()->json($result);
    }
      public function createBooking(Request $request)
    {
        $request->validate([
            'session_code' => 'required|string',
            'hotel_id' => 'required',
            'country_code' => 'required|string',
            'city_code' => 'required|string',
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date',
            // 'prebooking_file' => 'required|string'
        ]);

        $result = $this->hotelService->createHotelsBooking($request->all());

        return response()->json($result);
    }
}
