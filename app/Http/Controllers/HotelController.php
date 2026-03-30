<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\HotelServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        Log::info('Search request payload', $request->all());
        return response()->json(
            $this->hotelService->search(
                $request->all(),
                $request->input('arrival_date'),
                $request->input('departure_date')
            )
        );
    }


    public function amenities(string $hotelCode)
    {
        return response()->json(
            $this->hotelService->getHotelAmenities($hotelCode)
        );
    }
}
