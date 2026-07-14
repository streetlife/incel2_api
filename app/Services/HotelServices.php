<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingHotel;
use App\Models\Hotel;
use App\Models\HotelAmenity;
use App\Models\HotelCities;
use App\Models\HotelCountry;
use App\Models\HotelRoomAmenity;
use App\Models\HotelSession;
use App\Models\HotelSessionResult;
use App\Models\Markup;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Session\Session;

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
            ->get(['country_name as name', 'country_code as code']);
    }

    public function getCities(string $countryCode)
    {
        return HotelCities::where('country_code', $countryCode)
            ->orderBy('city_name')
            ->get(['city_name as name', 'city_code as code']);
    }

    public function getHotel(string $hotelCode)
    {
        return Hotel::where('hotel_code', $hotelCode)->first();
    }

    public  function getHotelBySessioncode(string $sessionCode)
    {
        $data = HotelSession::where('search_session_id', $sessionCode)->first();
        return $data;
    }
    public function getHotelSessionId($sessionCode, $hotelId)
    {
        $booking = HotelSessionResult::where('session_code', $sessionCode)
            ->where('hotel_id', $hotelId)
            ->first();
        return $booking;
    }
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

    // public function search(array $params, $arrivalDate, $departureDate)
    // {
    //     DB::beginTransaction();

    //     try {

    //         $sessionCode = Str::uuid()->toString();

    //         $result = $this->rezlive->searchHotels($params, $arrivalDate, $departureDate);

    //         if (isset($result['error'])) {
    //             throw new \Exception($result['error']);
    //         }

    //         $hotels = $result['data']['Hotels']['Hotel'] ?? [];

    //         if (isset($hotels['Id'])) {
    //             $hotels = [$hotels];
    //         }

    //         $hotelCount = count($hotels);

    //         $roomNumber = $params['room_number'] ?? 1;

    //         $totalAdults   = 0;
    //         $totalChildren = 0;

    //         for ($i = 1; $i <= $roomNumber; $i++) {
    //             $totalAdults   += (int) ($params["room{$i}_adults"]   ?? 0);
    //             $totalChildren += (int) ($params["room{$i}_children"] ?? 0);
    //         }

    //         HotelSession::create([
    //             'session_code'      => $sessionCode,
    //             'country_code'      => $params['search_hotel_country'] ?? null,
    //             'city_code'         => $params['search_hotel_city']    ?? null,
    //             'arrival_date'      => $arrivalDate,
    //             'departure_date'    => $departureDate,
    //             'currency'          => $result['data']['Currency'] ?? 'USD',
    //             'currency_code'     => $result['data']['Currency'] ?? 'USD',
    //             'result_count'      => $hotelCount,
    //             'rooms'             => $roomNumber,
    //             'adults'            => max(1, $totalAdults),
    //             'children'          => $totalChildren,
    //             'nationality'       => $params['search_hotel_nationality'] ?? null,
    //             'search_session_id' => $result['data']['SearchSessionId'] ?? null,
    //         ]);

    //         foreach ($hotels as $hotel) {

    //             if (empty($hotel['Id'])) {
    //                 continue;
    //             }

    //             $rooms = $hotel['RoomDetails']['RoomDetail'] ?? [];

    //             if (isset($rooms['RoomDescription'])) {
    //                 $rooms = [$rooms];
    //             }

    //             $boards    = [];
    //             $roomTypes = [];

    //             foreach ($rooms as $room) {

    //                 if (isset($room['RoomDescription'])) {
    //                     $roomDescription = $room['RoomDescription'];

    //                     if (is_string($roomDescription)) {
    //                         $boards = array_merge(
    //                             $boards,
    //                             array_map('trim', explode('|', $roomDescription))
    //                         );
    //                     } elseif (is_array($roomDescription)) {
    //                         array_walk_recursive($roomDescription, function ($value) use (&$boards) {
    //                             if (is_string($value) && !empty(trim($value))) {
    //                                 $boards[] = trim($value);
    //                             }
    //                         });
    //                     }
    //                 }

    //                 if (isset($room['Type'])) {
    //                     $type = $room['Type'];

    //                     if (is_string($type)) {
    //                         $roomTypes = array_merge(
    //                             $roomTypes,
    //                             array_map('trim', explode('|', $type))
    //                         );
    //                     } elseif (is_array($type)) {
    //                         array_walk_recursive($type, function ($value) use (&$roomTypes) {
    //                             if (is_string($value) && !empty(trim($value))) {
    //                                 $roomTypes[] = trim($value);
    //                             }
    //                         });
    //                     }
    //                 }
    //             }

    //             $boards    = array_values(array_unique($boards));
    //             $roomTypes = array_values(array_unique($roomTypes));

    //             $amenities = [
    //                 'roomcount'  => count($rooms),
    //                 'boardbasis' => $boards,
    //                 'room_types' => $roomTypes,
    //             ];

    //             HotelSessionResult::create([
    //                 'session_code' => $sessionCode,
    //                 'hotel_id'     => $hotel['Id'],
    //                 'hotel_rating' => $hotel['Rating'] ?? 0,
    //                 'hotel_thumbs' => '-',
    //                 'price'        => $hotel['Price'] ?? 0,
    //                 'room_count'   => $hotel['Hotelwiseroomcount'] ?? count($rooms),
    //                 'amenities'    => json_encode($amenities),
    //             ]);
    //         }

    //         DB::commit();

    //         $results = $this->searchHotelResult($sessionCode);

    //         return [
    //             'status'             => true,
    //             'message'            => 'Hotels fetched successfully',
    //             'session_code'       => $sessionCode,
    //             'search_session_id'  => $result['data']['SearchSessionId'] ?? null,

    //             'search_meta' => [
    //                 'arrival_date'   => $arrivalDate,
    //                 'departure_date' => $departureDate,
    //                 'rooms'          => $roomNumber,
    //                 'adults'         => $totalAdults,
    //                 'children'       => $totalChildren,
    //                 'currency'       => $result['data']['Currency'] ?? 'USD',
    //                 'result_count'   => $hotelCount,
    //             ],

    //             'filters'    => $results['filters'],
    //             'hotels'     => $results['hotels'],
    //             'bookingKey' => $result['booking_keys'],
    //         ];
    //     } catch (\Exception $e) {

    //         DB::rollBack();

    //         return [
    //             'status'  => false,
    //             'message' => $e->getMessage(),
    //         ];
    //     }
    // }
    public function search(array $params, $arrivalDate, $departureDate)
    {
        DB::beginTransaction();

        try {

            $sessionCode = Str::uuid()->toString();

            $result = $this->rezlive->searchHotels($params, $arrivalDate, $departureDate);

            if (isset($result['error'])) {
                throw new \Exception($result['error']);
            }

            $hotels = $result['data']['Hotels']['Hotel'] ?? [];

            if (isset($hotels['Id'])) {
                $hotels = [$hotels];
            }

            $hotelCount = count($hotels);

            $roomNumber = $params['room_number'] ?? 1;

            $roomsAdults       = [];
            $roomsChildren     = [];
            $roomsChildrenAges = [];
            $totalAdults       = 0;
            $totalChildren     = 0;

            for ($i = 1; $i <= $roomNumber; $i++) {
                $adults   = (int) ($params["room{$i}_adults"]   ?? 1);
                $children = (int) ($params["room{$i}_children"] ?? 0);

                $ages = [];
                for ($j = 1; $j <= $children; $j++) {
                    $ages[] = (int) ($params["room{$i}_child{$j}_age"] ?? 5);
                }

                $roomsAdults[]       = $adults;
                $roomsChildren[]     = $children;
                $roomsChildrenAges[] = $ages;
                $totalAdults        += $adults;
                $totalChildren      += $children;
            }

            HotelSession::create([
                'session_code'        => $sessionCode,
                'country_code'        => $params['search_hotel_country']     ?? null,
                'city_code'           => $params['search_hotel_city']        ?? null,
                'arrival_date'        => $arrivalDate,
                'departure_date'      => $departureDate,
                'currency'            => $result['data']['Currency'] ?? 'USD',
                'currency_code'       => $result['data']['Currency'] ?? 'USD',
                'result_count'        => $hotelCount,
                'rooms'               => $roomNumber,
                'adults'              => max(1, $totalAdults),
                'children'            => $totalChildren,
                'rooms_adults'        => json_encode($roomsAdults),
                'rooms_children'      => json_encode($roomsChildren),
                'rooms_children_ages' => json_encode($roomsChildrenAges),
                'nationality'         => $params['search_hotel_nationality'] ?? null,
                'search_session_id'   => $result['data']['SearchSessionId']  ?? null,
            ]);

            foreach ($hotels as $hotel) {
                //  log::info("booking key");
                //  log::info($hotel);
                if (empty($hotel['Id'])) {
                    continue;
                }

                $rooms = $hotel['RoomDetails']['RoomDetail'] ?? [];
               
                if (isset($rooms['RoomDescription'])) {
                    $rooms = [$rooms];
                }

                $boards    = [];
                $roomTypes = [];
                 $roomKey=[];
                foreach ($rooms as $room) {
                   
                    if(isset($room['BookingKey'])){
                        log::info($room['BookingKey']);
                           $roomKey = $room['BookingKey'];
                    }
                    if (isset($room['RoomDescription'])) {
                        $roomDescription = $room['RoomDescription'];

                        if (is_string($roomDescription)) {
                            $boards = array_merge(
                                $boards,
                                array_map('trim', explode('|', $roomDescription))
                            );
                        } elseif (is_array($roomDescription)) {
                            array_walk_recursive($roomDescription, function ($value) use (&$boards) {
                                if (is_string($value) && !empty(trim($value))) {
                                    $boards[] = trim($value);
                                }
                            });
                        }
                    }

                    if (isset($room['Type'])) {
                        $type = $room['Type'];
                        // Log::info($type);
                        if (is_string($type)) {
                            $roomTypes = array_merge(
                                $roomTypes,
                                array_map('trim', explode('|', $type))
                            );
                        } elseif (is_array($type)) {
                            array_walk_recursive($type, function ($value) use (&$roomTypes) {
                                if (is_string($value) && !empty(trim($value))) {
                                    $roomTypes[] = trim($value);
                                }
                            });
                        }
                    }
                }

                $boards    = array_values(array_unique($boards));
                $roomTypes = array_values(array_unique($roomTypes));

                $amenities = [
                    'roomcount'  => count($rooms),
                    'boardbasis' => $boards,
                    'room_types' => $roomTypes,
                ];

                HotelSessionResult::create([
                    'session_code' => $sessionCode,
                    'hotel_id'     => $hotel['Id'],
                    'hotel_rating' => $hotel['Rating'] ?? 0,
                    'hotel_thumbs' => '-',
                    'price'        => $hotel['Price'] ?? 0,
                    'room_count'   => $hotel['Hotelwiseroomcount'] ?? count($rooms),
                    'amenities'    => json_encode($amenities),
                    'booking_key'  => $roomKey
                ]);
            }

            DB::commit();

            $results = $this->searchHotelResult($sessionCode);

            return [
                'status'            => true,
                'message'           => 'Hotels fetched successfully',
                'session_code'      => $sessionCode,
                'search_session_id' => $result['data']['SearchSessionId'] ?? null,

                'search_meta' => [
                    'arrival_date'  => $arrivalDate,
                    'departure_date' => $departureDate,
                    'rooms'         => $roomNumber,
                    'adults'        => $totalAdults,
                    'children'      => $totalChildren,
                    'currency'      => $result['data']['Currency'] ?? 'USD',
                    'result_count'  => $hotelCount,
                ],

                'filters'    => $results['filters'],
                'hotels'     => $results['hotels'],
                'bookingKey' => $result['booking_keys'],
            ];
        } catch (\Exception $e) {

            DB::rollBack();

            return [
                'status'  => false,
                'message' => $e->getMessage(),
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
                'r.booking_key',
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
        $filterRoomTypes = [];
        // Log::info("filter", $filterBoardBasis);
        $results = [];

        foreach ($hotels as $hotel) {

            $amenities = json_decode($hotel->amenities, true) ?? [];

            if (is_string($amenities)) {
                $amenities = json_decode($amenities, true) ?? [];
            }

            $boardBasis = $amenities['boardbasis'] ?? [];
            $roomTypes = $amenities['room_types'] ??  [];
            // Log::info("boardBasis", $boardBasis);

            foreach ($boardBasis as $board) {
                $board = trim($board);

                if ($board !== '') {
                    $key = strtolower($board);
                    $filterBoardBasis[$key] = $board;
                }
            }
            foreach ($roomTypes as $roomType) {
                $roomType = trim($roomType);
                if ($roomType !== '') {
                    $key = strtolower($roomType);
                    $filterRoomTypes[$key] = $roomType;
                }
            }
            $filterRatings[$hotel->rating] = $hotel->rating;
            $filterCities[$hotel->city] = $hotel->city;
            $filterPrices[$hotel->price] = $hotel->price;

            $results[] = [
                'hotel_id'      => $hotel->hotel_id,
                'hotel_name'    => $hotel->hotel_name,
                'hotel_address' => $hotel->hotel_address,
                'city'          => $hotel->city,
                'rating'        => $hotel->rating,
                'thumbnail'     => $hotel->thumbnail,
                'price'         => $hotel->price,
                'room_count'    => $hotel->room_count,
                'board_basis'   => $boardBasis,
                'roomType'   =>  $roomTypes,
                'BookingKey' => $hotel->booking_key
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
public function getHotelDetail($sessionCode, $hotelId)
{
    $hotel = DB::table('sessions_hotels_results as r')
        ->join('hotels as h', 'r.hotel_id', '=', 'h.hotel_code')
        ->leftJoin('hotels_images as t', 't.hotel_code', '=', 'h.hotel_code')
        ->join('sessions_hotels as sh', 'sh.session_code', '=', 'r.session_code')
        ->where('r.session_code', $sessionCode)
        ->where('r.hotel_id', $hotelId)
        ->select(
            'r.hotel_id',
            'r.price',
            'r.room_count',
            'r.amenities',
            'r.booking_key',
            'h.hotel_name',
            'h.hotel_address',
            'h.city',
            'h.rating',
            'sh.rooms',
            'sh.departure_date',
            'sh.arrival_date',
            'sh.rooms_adults',
            'sh.rooms_children',
            'sh.rooms_children_ages',
            DB::raw('COALESCE(t.thumbnail_image,"images/img5.jpg") as thumbnail')
        )
        ->first();

    if (!$hotel) {
        return [
            'status'  => false,
            'message' => 'Hotel not found in this search session',
        ];
    }

    $amenities = json_decode($hotel->amenities, true) ?? [];
    if (is_string($amenities)) {
        $amenities = json_decode($amenities, true) ?? [];
    }

    $boardBasis = $amenities['boardbasis'] ?? [];
    $roomTypes  = $amenities['room_types'] ?? [];

    return [
        'status' => true,
        'hotel'  => [
            'hotel_id'            => $hotel->hotel_id,
            'hotel_name'          => $hotel->hotel_name,
            'hotel_address'       => $hotel->hotel_address,
            'city'                => $hotel->city,
            'rating'              => $hotel->rating,
            'thumbnail'           => $hotel->thumbnail,
            'price'               => $hotel->price,
            'room_count'          => $hotel->room_count,
            'board_basis'         => $boardBasis,
            'roomType'            => $roomTypes,
            'BookingKey'          => $hotel->booking_key,
            'totalRoom'           => $hotel->rooms,
            'arrivalDate'         => $hotel->arrival_date,
            'departureDate'       => $hotel->departure_date,
            'roomsAdult'          => json_decode($hotel->rooms_adults, true) ?? [],
            'roomsChildren'       => json_decode($hotel->rooms_children, true) ?? [],
            'roomsChildrenAges'   => json_decode($hotel->rooms_children_ages, true) ?? [],
        ],
    ];
}
    public function createHotelBooking($data)
    {


        $hotel = BookingHotel::create($data);

        return $hotel;
    }

    // public function createHotelsBooking($data)
    // {
    //     $userCode = auth()->user()->usercode ?? "temp" . now()->format('ymdHis');

    //     $sessionCode   = $data['session_code']    ?? null;
    //     $hotelId       = $data['hotel_id']        ?? null;
    //     $countryCode   = $data['country_code']    ?? null;
    //     $cityCode      = $data['city_code']       ?? null;
    //     $arrivalDate   = $data['arrival_date']    ?? null;
    //     $departureDate = $data['departure_date']  ?? null;
    //     $travellers    = $data['travellers']      ?? [];
    //     $roomType      = $data['rooms_type']      ?? null;
    //     $bookingKey    = $data['rooms_key']        ?? null;
    //     $totalRate     = $data['room_rates']       ?? 0;
    //     $hotelName     = $data['hotel_name']       ?? null;

    //     $convertDepartureDate = $this->changeDateFormatHotel($departureDate);
    //     $convertArrivalDate = $this->changeDateFormatHotel($arrivalDate);
    //     $roomsAdults   = $data['rooms_adults']   ?? [1];
    //     $roomsChildren = $data['rooms_children'] ?? [0];

    //     if (!is_array($roomsAdults))   $roomsAdults   = [$roomsAdults];
    //     if (!is_array($roomsChildren)) $roomsChildren = [$roomsChildren];

    //     if (!$sessionCode) {
    //         return ['status' => false, 'message' => 'Session code is required'];
    //     }

    //     if (!is_array($travellers) || count($travellers) === 0) {
    //         return ['status' => false, 'message' => 'At least one traveller is required'];
    //     }

    //     $hotelSession = $this->getHotelBySessioncode($sessionCode);

    //     if (!$hotelSession) {
    //         return ['status' => false, 'message' => 'Hotel session not found'];
    //     }

    //     $nationality     = $hotelSession->nationality      ?? null;
    //     $searchSessionId = $hotelSession->search_session_id ?? null;

    //     $bookingCode = $this->createBooking($userCode, 'HOTEL');
    //     $markup      = $this->getMarkup('HOTEL');

    //     $totalRates     = explode('|', $totalRate);
    //     $totalRoomCount = count($totalRates);

    //     $guests = [];
    //     foreach ($travellers as $traveller) {
    //         $guests[] = [
    //             'type'       => strtoupper($traveller['type'] ?? 'ADULT'),
    //             'first_name' => $traveller['first_name'] ?? '',
    //             'last_name'  => $traveller['last_name']  ?? '',
    //             'age'        => $traveller['age']         ?? null,
    //         ];
    //     }
    //     $rezliveHotelData = [
    //         'search_session_id' => $searchSessionId,
    //         'hotel_id'          => $hotelId,
    //         'hotel_name'        => $hotelName,
    //         'country_code'      => $countryCode,
    //         'city_code'         => $cityCode,
    //         'arrival_date'      => $convertArrivalDate,
    //         'departure_date'    => $convertDepartureDate,
    //         'nationality'       => $nationality,
    //         'room_type'         => $roomType,
    //         'booking_key'       => $bookingKey,
    //         'rooms_adults'      => $roomsAdults,
    //         'rooms_children'    => $roomsChildren,
    //         'total_rate'        => $totalRates[0] ?? 0,
    //         'guests'            => $guests,
    //     ];

    //     $rezliveResult = $this->rezlive->processBooking($bookingCode, [$rezliveHotelData]);

    //     if (!($rezliveResult['status'] ?? false)) {
    //         Log::error('Rezlive booking failed', [
    //             'booking_code' => $bookingCode,
    //             'error'        => $rezliveResult['message'] ?? 'Unknown error',
    //         ]);

    //         return [
    //             'status'  => false,
    //             'message' => $rezliveResult['message'] ?? 'Booking failed at provider',
    //         ];
    //     }

    //     for ($i = 0; $i < $totalRoomCount; $i++) {
    //         $roomRate       = $totalRates[$i] ?? 0;
    //         $roomRateMarkup = $this->priceMarkup($roomRate, $markup);

    //         foreach ($travellers as $traveller) {
    //             $this->createHotelBooking([
    //                 'booking_code'         => $bookingCode,
    //                 'booking_detail_code'  => 'BH' . now()->format('ymdHis') . rand(10, 99),
    //                 'session_id'           => $sessionCode,
    //                 'first_name'           => $traveller['first_name']  ?? null,
    //                 'last_name'            => $traveller['last_name']   ?? null,
    //                 'traveller_title'      => $traveller['title']       ?? null,
    //                 'hotel_id'             => $hotelId,
    //                 'country_code'         => $countryCode,
    //                 'city_code'            => $cityCode,
    //                 'arrival_date'         => $arrivalDate,
    //                 'departure_date'       => $departureDate,
    //                 'room_type'            => $roomType,
    //                 'nationality'          => $nationality,
    //                 'booking_key'          => $bookingKey,
    //                 'room_rate'            => $roomRate,
    //                 'room_rate_markup'     => $roomRateMarkup,
    //                 'rooms_adults'         => $roomsAdults[$i]   ?? 1,
    //                 'rooms_children'       => $roomsChildren[$i] ?? 0,
    //                 'total_rate'           => $totalRate,
    //                 'total_room_count'     => $totalRoomCount,
    //                 'provider_booking_ref' => $rezliveResult['provider_ref'] ?? null,
    //             ]);
    //         }
    //     }

    //     return [
    //         'status'       => true,
    //         'booking_code' => $bookingCode,
    //         'provider_ref' => $rezliveResult['provider_ref'] ?? null,
    //         'message'      => 'Booking created successfully',
    //     ];
    // }
    public function createHotelsBooking($data)
    {
        $userCode = auth()->user()->usercode ?? "temp" . now()->format('ymdHis');

        $sessionCode   = $data['session_code']    ?? null;
        $hotelId       = $data['hotel_id']        ?? null;
        $arrivalDate   = $data['arrival_date']    ?? null;
        $departureDate = $data['departure_date']  ?? null;
        $travellers    = $data['travellers']      ?? [];
        $roomType      = $data['rooms_type']      ?? null;
        $bookingKey    = $data['rooms_key']       ?? null;
        $totalRate     = $data['room_rates']      ?? 0;
        $hotelName     = $data['hotel_name']      ?? null;

        $roomsAdults   = $data['rooms_adults']   ?? [1];
        $roomsChildren = $data['rooms_children'] ?? [0];

        if (!is_array($roomsAdults))   $roomsAdults   = [$roomsAdults];
        if (!is_array($roomsChildren)) $roomsChildren = [$roomsChildren];

        if (!$sessionCode) {
            return ['status' => false, 'message' => 'Session code is required'];
        }

        if (!is_array($travellers) || count($travellers) === 0) {
            return ['status' => false, 'message' => 'At least one traveller is required'];
        }

        $hotelSession = $this->getHotelBySessioncode($sessionCode);

        if (!$hotelSession) {
            return ['status' => false, 'message' => 'Hotel session not found'];
        }

        $nationality     = $hotelSession->nationality       ?? null;
        $searchSessionId = $hotelSession->search_session_id ?? null;
        $countryCode     = $hotelSession->country_code;
        $cityCode        = $hotelSession->city_code;

        $roomsChildrenAges = $hotelSession->rooms_children_ages ?? '[]';
        if (is_string($roomsChildrenAges)) {
            $roomsChildrenAges = json_decode($roomsChildrenAges, true) ?? [];
        }

        $bookingCode = $this->createBooking($userCode, 'HOTEL');
        $markup      = $this->getMarkup('HOTEL');

        $roomTypes   = is_array($roomType)   ? $roomType   : explode('|', (string) $roomType);
        $bookingKeys = is_array($bookingKey) ? $bookingKey : explode('|', (string) $bookingKey);
        $totalRates  = is_array($totalRate)  ? $totalRate  : explode('|', (string) $totalRate);

        $totalRoomCount = count($totalRates);

        $cursor        = 0;
        $bookingHotels = [];

        for ($i = 0; $i < $totalRoomCount; $i++) {
            $roomSize       = ($roomsAdults[$i] ?? 1) + ($roomsChildren[$i] ?? 0);
            $roomTravellers = array_slice($travellers, $cursor, $roomSize);
            $cursor        += $roomSize;

            $guests = [];
            foreach ($roomTravellers as $traveller) {
                $guests[] = [
                    'type'       => strtoupper($traveller['type'] ?? 'ADULT'),
                    'first_name' => $traveller['first_name'] ?? '',
                    'last_name'  => $traveller['last_name']  ?? '',
                    'age'        => $traveller['age']        ?? null,
                ];
            }

            $bookingHotels[] = [
                'room_type'   => $roomTypes[$i]   ?? ($roomTypes[0] ?? null),
                'booking_key' => $bookingKeys[$i] ?? ($bookingKeys[0] ?? null),
                'total_rate'  => $totalRates[$i]  ?? 0,
                'guests'      => $guests,
            ];
        }

        $bookingHotels[0] = array_merge($bookingHotels[0], [
            'search_session_id'   => $searchSessionId,
            'hotel_id'            => $hotelId,
            'hotel_name'          => $hotelName,
            'country_code'        => $countryCode,
            'city_code'           => $cityCode,
            'arrival_date'        => $arrivalDate,
            'departure_date'      => $departureDate,
            'nationality'         => $nationality,
            'rooms_adults'        => $roomsAdults,
            'rooms_children'      => $roomsChildren,
            'rooms_children_ages' => $roomsChildrenAges,
        ]);

        $rezliveResult = $this->rezlive->processBooking($bookingCode, $bookingHotels);

        if (!($rezliveResult['status'] ?? false)) {
            Log::error('Rezlive booking failed', [
                'booking_code' => $bookingCode,
                'error'        => $rezliveResult['message'] ?? 'Unknown error',
            ]);

            return [
                'status'  => false,
                'message' => $rezliveResult['message'] ?? 'Booking failed at provider',
            ];
        }

        foreach ($bookingHotels as $i => $room) {
            $roomRate       = $room['total_rate'] ?? 0;
            $roomRateMarkup = $this->priceMarkup($roomRate, $markup);

            foreach ($room['guests'] as $guest) {
                $this->createHotelBooking([
                    'booking_code'         => $bookingCode,
                    'booking_detail_code'  => 'BH' . now()->format('ymdHis') . rand(10, 99),
                    'session_id'           => $sessionCode,
                    'first_name'           => $guest['first_name'] ?? null,
                    'last_name'            => $guest['last_name']  ?? null,
                    'traveller_title'      => $guest['type'] === 'CHILD' ? 'Child' : 'Mr',
                    'hotel_id'             => $hotelId,
                    'country_code'         => $countryCode,
                    'city_code'            => $cityCode,
                    'arrival_date'         => $arrivalDate,
                    'departure_date'       => $departureDate,
                    'room_type'            => $room['room_type']   ?? null,
                    'nationality'          => $nationality,
                    'booking_key'          => $room['booking_key'] ?? null,
                    'room_rate'            => $roomRate,
                    'room_rate_markup'     => $roomRateMarkup,
                    'rooms_adults'         => $roomsAdults[$i]   ?? 1,
                    'rooms_children'       => $roomsChildren[$i] ?? 0,
                    'total_rate'           => $totalRate,
                    'total_room_count'     => $totalRoomCount,
                    'provider_booking_ref' => $rezliveResult['provider_ref'] ?? null,
                ]);
            }
        }

        return [
            'status'       => true,
            'booking_code' => $bookingCode,
            'provider_ref' => $rezliveResult['provider_ref'] ?? null,
            'message'      => 'Booking created successfully',
        ];
    }

    public function changeDateFormatHotel($date)
    {
        if (!$date) {
            return null;
        }

        return Carbon::parse($date)->format('d/m/Y');
    }
    public function createBooking($usercode, $bookingType)
    {
        $bookingCode = 'B' . now()->format('ymdHis');

        $booking = Booking::create([
            'usercode'       => $usercode,
            'booking_code'   => $bookingCode,
            'booking_type'   => $bookingType,
            'booking_status' => 'NEW',
            'date_expiry'    => Carbon::now()->addMinutes(config('booking.expiry', 60))
        ]);

        return $booking['booking_code'];
    }
    public function getMarkup(string $module)
    {
        $customerMode = 'B2C';
        // if (Auth::check()) {
        //     $accessLevel = Auth::user()->access_level;

        //     if (in_array($accessLevel, ['AGENT', 'ADMIN'])) {
        //         $customerMode = 'B2B';
        //     }
        // }

        return Markup::where('module', $module)
            ->where('customer_type', $customerMode)
            ->first();
    }

    public function priceMarkup(float $amount, $markup): float
    {
        if ($amount <= 0) {
            return 0;
        }

        if (!$markup) {
            return $amount;
        }


        $markupType = is_array($markup)
            ? ($markup['markup_type'] ?? null)
            : ($markup->markup_type ?? null);

        $markupAmount = is_array($markup)
            ? ($markup['markup_amount'] ?? 0)
            : ($markup->markup_amount ?? 0);

        $newAmount = $amount;

        if ($markupType === 'FLAT') {
            $newAmount = $amount + (float) $markupAmount;
        }

        if ($markupType === 'PERCENTAGE') {
            $newAmount = $amount + ((float) $markupAmount / 100) * $amount;
        }

        return round($newAmount, 2);
    }
}
