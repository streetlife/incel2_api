<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\HotelAmenity;
use App\Models\HotelCities;
use App\Models\HotelCountry;
use App\Models\HotelRoomAmenity;
use App\Models\HotelSession;
use App\Models\HotelSessionResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HotelServices
{
    protected RezliveServices $rezlive;

    public function __construct(RezliveServices $rezlive)
    {
        $this->rezlive = $rezlive;
    }
    public function getCountries()
    {
        return HotelCountry::orderBy('country_name')
            ->pluck('country_name', 'country_code');
    }

    public function getCities(string $countryCode)
    {
        return HotelCities::where('country_code', $countryCode)
            ->orderBy('city_name')
            ->pluck('city_name', 'city_code');
    }

    public function getHotel(string $hotelCode)
    {
        return Hotel::where('hotel_code', $hotelCode)->first();
    }

    // public function search(array $params, $arrivalDate, $departureDate)
    // {
    //     DB::beginTransaction();

    //     try {
    //         Log::info('Service received params', [
    //             'params' => $params,
    //             'arrivalDate' => $arrivalDate,
    //             'departureDate' => $departureDate
    //         ]);

    //         $sessionCode = Str::uuid()->toString();
    //         $arrivalDate = date('d/m/Y', strtotime($arrivalDate));
    //         $departureDate = date('d/m/Y', strtotime($departureDate));
    //         Log::info('Service received params', [
    //             'params' => $params,
    //             'arrivalDate' => $arrivalDate,
    //             'departureDate' => $departureDate
    //         ]);
    //         $result = $this->rezlive->searchHotels(
    //             $params,
    //             $arrivalDate,
    //             $departureDate
    //         );
    //         Log::info('resut', ['result' => $result]);
    //         $details = $result['data']['Hotels']['Hotel'];
    //         Log::info('hotels', $details);
    //         if (isset($result['error'])) {
    //             throw new \Exception($result['error']);
    //         }

    //         if (isset($result['Hotels']['Error'])) {
    //             throw new \Exception('No result returned from supplier.');
    //         }

    //         $hotels = $result['data']['Hotels']['Hotel'] ?? [];
    //         $hotelCount = count($hotels);

    //         HotelSession::create([
    //             'session_code' => $sessionCode,
    //             'country_code' => $params['search_hotel_country'] ?? null,
    //             'city_code' => $params['search_hotel_city'] ?? null,
    //             'arrival_date' => $arrivalDate,
    //             'departure_date' => $departureDate,
    //             'currency' => $result['Currency'] ?? 'USD',
    //             'currency_code' => $result['Currency'] ?? 'USD',
    //             'result_count' => $hotelCount,
    //             'rooms' => $params['room_number'] ?? null,
    //             'adults' => max(1, $params['adult_number']) ?? 0,
    //             'children' => $params['child_number'] ?? 0,
    //             'nationality' => $params['search_hotel_nationality'] ?? null,
    //             'search_session_id' => $result['data']['SearchSessionId'] ?? null
    //         ]);


    //         foreach ($hotels as $hotel) {

    //             if (empty($hotel['Id'])) continue;

    //             $rooms = $hotel['RoomDetails']['RoomDetail'] ?? [];
    //             Log::info("room", $rooms);
    //             $boards = [];

    //             foreach ($rooms as $room) {
    //                 // Log::info("ddd", $room);
    //                 if (isset($room['RoomDescription'])) {
    //                     $roomDescriptionArray = explode('|', $room['RoomDescription']);
    //                     $boards[$room['RoomDescription']] = $roomDescriptionArray;
    //                 }
    //             }

    //             $amenities = [
    //                 'roomcount' => count($rooms),
    //                 'boardbasis' => $boards
    //             ];

    //             HotelSessionResult::create([
    //                 'session_code' => $sessionCode,
    //                 'hotel_id' => $hotel['Id'],
    //                 'hotel_rating' => $hotel['Rating'],
    //                 'hotel_thumbs' => '-',
    //                 'price' => $hotel['Price'],
    //                 'room_count' => $hotel['Hotelwiseroomcount'],
    //                 'amenities' => json_encode($amenities)
    //             ]);
    //         }

    //         DB::commit();

    //         return [
    //             'status' => true,
    //             'session_code' => $sessionCode,
    //             'result_count' => $hotelCount
    //         ];
    //     } catch (Exception $e) {

    //         DB::rollBack();

    //         return [
    //             'status' => false,
    //             'message' => $e->getMessage()
    //         ];
    //     }
    // }
    // public function search(array $params, $arrivalDate, $departureDate)
    // {
    //     DB::beginTransaction();

    //     try {
    //         $sessionCode = Str::uuid()->toString();
    //         $arrivalDateFormatted = date('d/m/Y', strtotime($arrivalDate));
    //         $departureDateFormatted = date('d/m/Y', strtotime($departureDate));

    //         $result = $this->rezlive->searchHotels($params, $arrivalDateFormatted, $departureDateFormatted);
    //     Log::info('result',$result);
    //         if (isset($result['error'])) throw new \Exception($result['error']);

    //         $hotels = $result['data']['Hotels']['Hotel'] ?? [];
    //         $hotelCount = count($hotels);
    //         $totalAdults = 0;
    //         $totalChildren = 0;
    //         $roomNumber = $params['room_number'] ?? 1;

    //         for ($i = 1; $i <= $roomNumber; $i++) {
    //             $totalAdults += (int)($params["room{$i}_adults"] ?? 0);
    //             $totalChildren += (int)($params["room{$i}_children"] ?? 0);
    //         }
           

    //         HotelSession::create([
    //             'session_code'      => $sessionCode,
    //             'country_code'      => $params['search_hotel_country'] ?? null,
    //             'city_code'         => $params['search_hotel_city'] ?? null,
    //             'arrival_date'      => $arrivalDate,
    //             'departure_date'    => $departureDate,
    //             'currency'          => $result['data']['Currency'] ?? 'USD',
    //             'currency_code'     => $result['data']['Currency'] ?? 'USD',
    //             'result_count'      => $hotelCount,
    //             'rooms'             => $roomNumber,
    //             'adults'            => max(1, $totalAdults), // Use the calculated total
    //             'children'          => $totalChildren,
    //             'nationality'       => $params['search_hotel_nationality'] ?? null,
    //             'search_session_id' => $result['data']['SearchSessionId'] ?? null
    //         ]);
    //         if (isset($hotels['Id'])) {
    //             $hotels = [$hotels];
    //         }
    //         foreach ($hotels as $hotel) {
    //             if (empty($hotel['Id'])) continue;

    //             $rooms = $hotel['RoomDetails']['RoomDetail'] ?? [];
    //             $boards = [];

    //             foreach ($rooms as $room) {
    //                 if (isset($room['RoomDescription'])) {
    //                     $descriptionParts = explode('|', $room['RoomDescription']);
    //                     $boards[] = array_map('trim', $descriptionParts);
    //                 }
    //             }
    //             $amenities = [
    //                 'roomcount' => count($rooms),
    //                 'boardbasis' => $boards
    //             ];

    //             HotelSessionResult::create([
    //                 'session_code' => $sessionCode,
    //                 'hotel_id'     => $hotel['Id'],
    //                 'hotel_rating' => $hotel['Rating'],
    //                 'hotel_thumbs' => '-',
    //                 'price'        => $hotel['Price'],
    //                 'room_count'   => $hotel['Hotelwiseroomcount'] ?? count($rooms),
    //                 'amenities'    => json_encode($amenities)
    //             ]);
    //         }

    //         DB::commit();

    //         return [
    //             'data' =>  $this->searchHotelResult($sessionCode),
    //             'result_count' => $hotelCount
    //         ];
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return [
    //             'status' => false,
    //             'message' => $e->getMessage()
    //         ];
    //     }
    // }
    public function getHotelAmenities(string $hotelCode): array
    {
        $propertyAmenities = HotelAmenity::where('hotel_code', $hotelCode)
            ->value('amenities');

        $roomAmenities = HotelRoomAmenity::where('hotel_code', $hotelCode)
            ->value('amenities');

        return [
            'property' => $propertyAmenities ?? '',
            'rooms' => $roomAmenities ?? ''
        ];
    }
    // public function searchHotelResult($session_code)
    // {
    //     $session = DB::table('sessions_hotels')
    //         ->where('session_code', $session_code)
    //         ->first();

    //     if (!$session) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Session not found'
    //         ], 404);
    //     }

    //     $hotelBookings = DB::table('sessions_hotels_results as r')
    //         ->join('hotels as h', 'r.hotel_id', '=', 'h.hotel_code')
    //         ->leftJoin('hotels_images as t', 't.hotel_code', '=', 'h.hotel_code')
    //         ->where('r.session_code', $session_code)
    //         ->select(
    //             'r.*',
    //             'h.hotel_name',
    //             'h.hotel_address',
    //             'h.city',
    //             'h.rating',
    //             DB::raw('COALESCE(t.thumbnail_image,"images/img5.jpg") as thumbnail')
    //         )
    //         ->get();

    //     $filterRatings = [];
    //     $filterPrices = [];
    //     $filterCities = [];
    //     $filterBoardBasis = [];

    //     $results = [];

    //     foreach ($hotelBookings as $hotel) {

    //         $amenities = json_decode($hotel->amenities, true);

    //         $boardBasisOptions = $amenities['boardbasis'] ?? [];
    //         $roomCount = $amenities['roomcount'] ?? 0;

    //         foreach ($boardBasisOptions as $board) {
    //             $filterBoardBasis[$board] = $board;
    //         }

    //         $filterRatings[$hotel->rating] = $hotel->rating;
    //         $filterCities[$hotel->city] = $hotel->city;

    //         $filterPrices[$hotel->price] = $hotel->price;

    //         $results []= [
    //             "hotel_id" => $hotel->hotel_id,
    //             "hotel_name" => $hotel->hotel_name,
    //             "hotel_address" => $hotel->hotel_address,
    //             "city" => $hotel->city,
    //             "rating" => $hotel->rating,
    //             "thumbnail" => $hotel->thumbnail,
    //             "price" => $hotel->price,
    //             "room_count" => $roomCount,
    //             "board_basis" => $boardBasisOptions
    //         ];
    //     }

    //     krsort($filterRatings);
    //     asort($filterPrices);
    //     asort($filterCities);
    //     asort($filterBoardBasis);

    //     return response()->json([
    //          $results,
    //         "filters" => [
    //             "ratings" => array_values($filterRatings),
    //             "prices" => array_values($filterPrices),
    //             "cities" => array_values($filterCities),
    //             "boardbasis" => array_values($filterBoardBasis)
    //         ]
    //     ]);
    // }
      public function search(array $params, $arrivalDate, $departureDate)
    {
        DB::beginTransaction();

        try {

            $sessionCode = Str::uuid()->toString();

            $arrivalDateFormatted = date('d/m/Y', strtotime($arrivalDate));
            $departureDateFormatted = date('d/m/Y', strtotime($departureDate));

            $result = $this->rezlive->searchHotels($params, $arrivalDateFormatted, $departureDateFormatted);

            Log::info('Rezlive hotel search response', $result);

            if (isset($result['error'])) {
                throw new \Exception($result['error']);
            }

            $hotels = $result['data']['Hotels']['Hotel'] ?? [];

            if (isset($hotels['Id'])) {
                $hotels = [$hotels];
            }

            $hotelCount = count($hotels);

            $roomNumber = $params['room_number'] ?? 1;

            $totalAdults = 0;
            $totalChildren = 0;

            for ($i = 1; $i <= $roomNumber; $i++) {
                $totalAdults += (int)($params["room{$i}_adults"] ?? 0);
                $totalChildren += (int)($params["room{$i}_children"] ?? 0);
            }

            HotelSession::create([
                'session_code' => $sessionCode,
                'country_code' => $params['search_hotel_country'] ?? null,
                'city_code' => $params['search_hotel_city'] ?? null,
                'arrival_date' => $arrivalDate,
                'departure_date' => $departureDate,
                'currency' => $result['data']['Currency'] ?? 'USD',
                'currency_code' => $result['data']['Currency'] ?? 'USD',
                'result_count' => $hotelCount,
                'rooms' => $roomNumber,
                'adults' => max(1, $totalAdults),
                'children' => $totalChildren,
                'nationality' => $params['search_hotel_nationality'] ?? null,
                'search_session_id' => $result['data']['SearchSessionId'] ?? null
            ]);

            foreach ($hotels as $hotel) {

                if (empty($hotel['Id'])) {
                    continue;
                }

                $rooms = $hotel['RoomDetails']['RoomDetail'] ?? [];

                if (isset($rooms['RoomDescription'])) {
                    $rooms = [$rooms];
                }

                $boards = [];

                foreach ($rooms as $room) {

                    if (!isset($room['RoomDescription'])) {
                        continue;
                    }

                    $parts = explode('|', $room['RoomDescription']);

                    foreach ($parts as $part) {
                        $boards[] = trim($part);
                    }
                }

                $boards = array_values(array_unique($boards));

                $amenities = [
                    'roomcount' => count($rooms),
                    'boardbasis' => $boards
                ];

                HotelSessionResult::create([
                    'session_code' => $sessionCode,
                    'hotel_id' => $hotel['Id'],
                    'hotel_rating' => $hotel['Rating'] ?? 0,
                    'hotel_thumbs' => '-',
                    'price' => $hotel['Price'] ?? 0,
                    'room_count' => $hotel['Hotelwiseroomcount'] ?? count($rooms),
                    'amenities' => json_encode($amenities)
                ]);
            }

            DB::commit();

            $results = $this->searchHotelResult($sessionCode);

            return [
                'status' => true,
                'message' => 'Hotels fetched successfully',
                'session_code' => $sessionCode,

                'search_meta' => [
                    'arrival_date' => $arrivalDate,
                    'departure_date' => $departureDate,
                    'rooms' => $roomNumber,
                    'adults' => $totalAdults,
                    'children' => $totalChildren,
                    'currency' => $result['data']['Currency'] ?? 'USD',
                    'result_count' => $hotelCount
                ],

                'filters' => $results['filters'],
                'hotels' => $results['hotels']
            ];

        } catch (\Exception $e) {

            DB::rollBack();

            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }


    public function searchHotelResult($sessionCode)
    {
        $hotels = DB::table('sessions_hotels_results as r')
            ->join('hotels as h', 'r.hotel_id', '=', 'h.hotel_code')
            ->leftJoin('hotels_images as t', 't.hotel_code', '=', 'h.hotel_code')
            ->where('r.session_code', $sessionCode)
            ->select(
                'r.hotel_id',
                'r.price',
                'r.room_count',
                'r.amenities',
                'h.hotel_name',
                'h.hotel_address',
                'h.city',
                'h.rating',
                DB::raw('COALESCE(t.thumbnail_image,"images/img5.jpg") as thumbnail')
            )
            ->get();

        $filterRatings = [];
        $filterPrices = [];
        $filterCities = [];
        $filterBoardBasis = [];

        $results = [];

        foreach ($hotels as $hotel) {

            $amenities = json_decode($hotel->amenities, true);

            $boardBasis = $amenities['boardbasis'] ?? [];

            foreach ($boardBasis as $board) {
                $filterBoardBasis[$board] = $board;
            }

            $filterRatings[$hotel->rating] = $hotel->rating;
            $filterCities[$hotel->city] = $hotel->city;
            $filterPrices[$hotel->price] = $hotel->price;

            $results[] = [
                'hotel_id' => $hotel->hotel_id,
                'hotel_name' => $hotel->hotel_name,
                'hotel_address' => $hotel->hotel_address,
                'city' => $hotel->city,
                'rating' => $hotel->rating,
                'thumbnail' => $hotel->thumbnail,
                'price' => $hotel->price,
                'room_count' => $hotel->room_count,
                'board_basis' => $boardBasis
            ];
        }

        krsort($filterRatings);
        asort($filterPrices);
        asort($filterCities);
        asort($filterBoardBasis);

        return [
            'filters' => [
                'ratings' => array_values($filterRatings),
                'prices' => array_values($filterPrices),
                'cities' => array_values($filterCities),
                'boardbasis' => array_values($filterBoardBasis)
            ],
            'hotels' => $results
        ];
    }
}
