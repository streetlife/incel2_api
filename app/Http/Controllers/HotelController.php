<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\HotelServices;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    protected HotelServices $hotelService;

    public function __construct(HotelServices $hotelService)
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
        $validated = $request->validate([
            'search_hotel_nationality' => 'required|string',
            'search_hotel_country' => 'required|string',
            'search_hotel_city' => 'required|string',
            'daterange' => 'required|string',
            'room_number' => 'required|integer|min:1',
            'adult_number' => 'required|integer|min:1',
        ]);

        return response()->json(
            $this->hotelService->search($request->all())
        );
    }

   
    public function amenities(string $hotelCode)
    {
        return response()->json(
            $this->hotelService->getHotelAmenities($hotelCode)
        );
    }
}
