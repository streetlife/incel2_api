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
use Psy\Readline\Hoa\Console;
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

    //         $result = $this->rezlive->searchHotels($params, $arrivalDate, $departureDate, $sessionCode);

    //         if (isset($result['error'])) {
    //             throw new \Exception($result['error']);
    //         }

    //         $hotels = $result['data']['Hotels']['Hotel'] ?? [];

    //         if (isset($hotels['Id'])) {
    //             $hotels = [$hotels];
    //         }

    //         if (empty($hotels)) {
    //             DB::rollBack();

    //             return [
    //                 'status'  => false,
    //                 'statusCode' => 404,
    //                 'message' => 'No hotels found for your search. Try different dates or a nearby city.',
    //             ];
    //         }
    //         $hotelCount = count($hotels);

    //         $roomNumber = $params['room_number'] ?? 1;

    //         $roomsAdults       = [];
    //         $roomsChildren     = [];
    //         $roomsChildrenAges = [];
    //         $totalAdults       = 0;
    //         $totalChildren     = 0;

    //         for ($i = 1; $i <= $roomNumber; $i++) {
    //             $adults   = (int) ($params["room{$i}_adults"]   ?? 1);
    //             $children = (int) ($params["room{$i}_children"] ?? 0);

    //             $ages = [];
    //             for ($j = 1; $j <= $children; $j++) {
    //                 $ages[] = (int) ($params["room{$i}_child{$j}_age"] ?? 5);
    //             }

    //             $roomsAdults[]       = $adults;
    //             $roomsChildren[]     = $children;
    //             $roomsChildrenAges[] = $ages;
    //             $totalAdults        += $adults;
    //             $totalChildren      += $children;
    //         }

    //         HotelSession::create([
    //             'session_code'        => $sessionCode,
    //             'country_code'        => $params['search_hotel_country']     ?? null,
    //             'city_code'           => $params['search_hotel_city']        ?? null,
    //             'arrival_date'        => $arrivalDate,
    //             'departure_date'      => $departureDate,
    //             'currency'            => $result['data']['Currency'] ?? 'USD',
    //             'currency_code'       => $result['data']['Currency'] ?? 'USD',
    //             'result_count'        => $hotelCount,
    //             'rooms'               => $roomNumber,
    //             'adults'              => max(1, $totalAdults),
    //             'children'            => $totalChildren,
    //             'rooms_adults'        => json_encode($roomsAdults),
    //             'rooms_children'      => json_encode($roomsChildren),
    //             'rooms_children_ages' => json_encode($roomsChildrenAges),
    //             'nationality'         => $params['search_hotel_nationality'] ?? null,
    //             'search_session_id'   => $result['data']['SearchSessionId']  ?? null,
    //         ]);


    //         foreach ($hotels as $hotel) {

    //             if (empty($hotel['Id'])) {
    //                 continue;
    //             }

    //             $rooms = $hotel['RoomDetails']['RoomDetail'] ?? [];

    //             if (isset($rooms['RoomDescription'])) {
    //                 $rooms = [$rooms];
    //             }

    //             $boards     = [];
    //             $roomTypes  = [];
    //             $bookingKey = null;
    //             $cancellationPolicy = null;
    //             // $TermsAndConditions = null;

    //             foreach ($rooms as $room) {

    //                 if ($bookingKey === null && isset($room['BookingKey']) && is_string($room['BookingKey'])) {
    //                     $bookingKey = trim($room['BookingKey']);
    //                     $cancellationPolicy = $room['CancellationPolicy'] ?? null;
    //                     // $TermsAndConditions = $room['TermsAndConditions'] ?? null;
    //                 }

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
    //                 'booking_key'  => $bookingKey,
    //                 'cancellation_policy' => $cancellationPolicy ? json_encode($cancellationPolicy) : null,
    //             ]);
    //         }

    //         DB::commit();

    //         $results = $this->searchHotelResult($sessionCode);

    //         return [
    //             'status'            => true,
    //             'message'           => 'Hotels fetched successfully',
    //             'session_code'      => $sessionCode,
    //             'search_session_id' => $result['data']['SearchSessionId'] ?? null,

    //             'search_meta' => [
    //                 'arrival_date'   => $arrivalDate,
    //                 'departure_date' => $departureDate,
    //                 'rooms'          => $roomNumber,
    //                 'adults'         => $totalAdults,
    //                 'children'       => $totalChildren,
    //                 'currency'       => $result['data']['Currency'] ?? 'USD',
    //                 'result_count'   => $hotelCount,

    //             ],

    //             'filters' => $results['filters'],
    //             'hotels'  => $results['hotels'],
    //         ];
    //     } catch (\Exception $e) {

    //         DB::rollBack();

    //         return [
    //             'status'  => false,
    //             'message' => 'Something went wrong while searching for hotels. Please try again.',
    //         ];
    //     }
    // }

    public function search(array $params, $arrivalDate, $departureDate)
{
    DB::beginTransaction();
 
    try {
        $sessionCode = Str::uuid()->toString();
 
        $result = $this->rezlive->searchHotels($params, $arrivalDate, $departureDate, $sessionCode);
 
        if (isset($result['error'])) {
            throw new \Exception($result['error']);
        }
 
        $hotels = $result['data']['Hotels']['Hotel'] ?? [];
 
        // Single hotel comes back as an assoc array, not a list
        if (isset($hotels['Id'])) {
            $hotels = [$hotels];
        }
 
        if (empty($hotels)) {
            DB::rollBack();
 
            return [
                'status'     => false,
                'statusCode' => 404,
                'message'    => 'No hotels found for your search. Try different dates or a nearby city.',
            ];
        }
 
        // ---------- Room / guest breakdown ----------
        $roomNumber        = (int) ($params['room_number'] ?? 1);
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
 
        // ---------- Group Rezlive hotels by Id (dedupe + merge rooms) ----------
        $grouped = [];
 
        foreach ($hotels as $hotel) {
            if (empty($hotel['Id'])) {
                continue;
            }
 
            $hotelId = (string) $hotel['Id'];
 
            $rooms = $hotel['RoomDetails']['RoomDetail'] ?? [];
            if (isset($rooms['RoomDescription'])) {
                $rooms = [$rooms];
            }
 
            $boards    = [];
            $roomTypes = [];
            $roomList  = [];
 
            foreach ($rooms as $room) {
                $descStrings = $this->extractStrings($room['RoomDescription'] ?? null);
                $typeStrings = $this->extractStrings($room['Type'] ?? null);
 
                $boards    = array_merge($boards, $descStrings);
                $roomTypes = array_merge($roomTypes, $typeStrings);
 
                $roomList[] = [
                    'booking_key' => (isset($room['BookingKey']) && is_string($room['BookingKey']))
                        ? trim($room['BookingKey'])
                        : null,
                    'description'         => $descStrings,
                    'type'                => $typeStrings,
                    'total_rooms'         => $room['TotalRooms'] ?? null,
                    'price'               => $room['TotalRate'] ?? null,
                    'cancellation_policy' => $room['CancellationPolicy'] ?? null,
                ];
            }
 
            $price = (float) ($hotel['Price'] ?? 0);
 
            if (isset($grouped[$hotelId])) {
                // Same hotel returned again: merge rooms, keep the lowest non-zero price
                $g = &$grouped[$hotelId];
                $g['rooms']      = array_merge($g['rooms'], $roomList);
                $g['boards']     = array_values(array_unique(array_merge($g['boards'], $boards)));
                $g['room_types'] = array_values(array_unique(array_merge($g['room_types'], $roomTypes)));
 
                if ($price > 0 && ($g['price'] == 0 || $price < $g['price'])) {
                    $g['price'] = $price;
                }
                unset($g);
                continue;
            }
 
            $grouped[$hotelId] = [
                'rating'     => $hotel['Rating'] ?? 0,
                'price'      => $price,
                'rooms'      => $roomList,
                'boards'     => array_values(array_unique($boards)),
                'room_types' => array_values(array_unique($roomTypes)),
            ];
        }
 
        $hotelCount = count($grouped);
 
        if ($hotelCount === 0) {
            DB::rollBack();
 
            return [
                'status'     => false,
                'statusCode' => 404,
                'message'    => 'No hotels found for your search. Try different dates or a nearby city.',
            ];
        }
 
        // ---------- Save session ----------
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
 
        // ---------- Save one result row per hotel ----------
        foreach ($grouped as $hotelId => $row) {
            $firstRoom = $row['rooms'][0] ?? [];
 
            HotelSessionResult::updateOrCreate(
                [
                    'session_code' => $sessionCode,
                    'hotel_id'     => $hotelId,
                ],
                [
                    'hotel_rating' => $row['rating'],
                    'hotel_thumbs' => '-',
                    'price'        => $row['price'],
                    'room_count'   => count($row['rooms']),
                    'amenities'    => json_encode([
                        'roomcount'  => count($row['rooms']),
                        'boardbasis' => $row['boards'],
                        'room_types' => $row['room_types'],
                        'rooms'      => $row['rooms'], // every room with its own booking key
                    ]),
                    'booking_key'         => $firstRoom['booking_key'] ?? null,
                    'cancellation_policy' => !empty($firstRoom['cancellation_policy'])
                        ? json_encode($firstRoom['cancellation_policy'])
                        : null,
                ]
            );
        }
 
        DB::commit();
 
        $results = $this->searchHotelResult($sessionCode);
 
        return [
            'status'            => true,
            'message'           => 'Hotels fetched successfully',
            'session_code'      => $sessionCode,
            'search_session_id' => $result['data']['SearchSessionId'] ?? null,
 
            'search_meta' => [
                'arrival_date'   => $arrivalDate,
                'departure_date' => $departureDate,
                'rooms'          => $roomNumber,
                'adults'         => $totalAdults,
                'children'       => $totalChildren,
                'currency'       => $result['data']['Currency'] ?? 'USD',
                'result_count'   => $hotelCount,
            ],
 
            'filters' => $results['filters'],
            'hotels'  => $results['hotels'],
        ];
    } catch (\Exception $e) {
        DB::rollBack();
 
        Log::error('Hotel search failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
 
        return [
            'status'  => false,
            'message' => 'Something went wrong while searching for hotels. Please try again.',
        ];
    }
}

    // public function searchHotelResult($sessionCode)
    // {
    //     $hotels = DB::table('sessions_hotels_results as r')
    //         ->join('hotels as h', 'r.hotel_id', '=', 'h.hotel_code')
    //         ->leftJoin('hotels_images as t', 't.hotel_code', '=', 'h.hotel_code')
    //         ->where('r.session_code', $sessionCode)
    //         ->select(
    //             'r.hotel_id',
    //             'r.price',
    //             'r.room_count',
    //             'r.amenities',
    //             'r.booking_key',
    //             'r.cancellation_policy',
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
    //     $filterRoomTypes = [];
    //     // Log::info("filter", $filterBoardBasis);
    //     $results = [];

    //     foreach ($hotels as $hotel) {

    //         $amenities = json_decode($hotel->amenities, true) ?? [];

    //         if (is_string($amenities)) {
    //             $amenities = json_decode($amenities, true) ?? [];
    //         }

    //         $boardBasis = $amenities['boardbasis'] ?? [];
    //         $roomTypes = $amenities['room_types'] ??  [];
    //         // Log::info("boardBasis", $boardBasis);

    //         foreach ($boardBasis as $board) {
    //             $board = trim($board);

    //             if ($board !== '') {
    //                 $key = strtolower($board);
    //                 $filterBoardBasis[$key] = $board;
    //             }
    //         }
    //         foreach ($roomTypes as $roomType) {
    //             $roomType = trim($roomType);
    //             if ($roomType !== '') {
    //                 $key = strtolower($roomType);
    //                 $filterRoomTypes[$key] = $roomType;
    //             }
    //         }
    //         $filterRatings[$hotel->rating] = $hotel->rating;
    //         $filterCities[$hotel->city] = $hotel->city;
    //         $filterPrices[$hotel->price] = $hotel->price;

    //         $results[] = [
    //             'hotel_id'      => $hotel->hotel_id,
    //             'hotel_name'    => $hotel->hotel_name,
    //             'hotel_address' => $hotel->hotel_address,
    //             'city'          => $hotel->city,
    //             'rating'        => $hotel->rating,
    //             'thumbnail'     => $hotel->thumbnail,
    //             'price'         => $hotel->price,
    //             'room_count'    => $hotel->room_count,
    //             'board_basis'   => $boardBasis,
    //             'roomType'   =>  $roomTypes,
    //             'BookingKey' => $hotel->booking_key,
    //             'cancellation_policy' => json_decode($hotel->cancellation_policy)
    //         ];
    //     }

    //     krsort($filterRatings);
    //     asort($filterPrices);
    //     asort($filterCities);
    //     asort($filterBoardBasis);

    //     return [
    //         'filters' => [
    //             'ratings' => array_values($filterRatings),
    //             'prices' => array_values($filterPrices),
    //             'cities' => array_values($filterCities),
    //             'boardbasis' => array_values($filterBoardBasis)
    //         ],
    //         'hotels' => $results

    //     ];
    // }
    private function extractStrings($value): array
    {
        $out = [];

        if (is_string($value)) {
            foreach (explode('|', $value) as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $out[] = $part;
                }
            }
        } elseif (is_array($value)) {
            array_walk_recursive($value, function ($v) use (&$out) {
                if (is_string($v) && trim($v) !== '') {
                    $out[] = trim($v);
                }
            });
        }

        return $out;
    }
    public function searchHotelResult($sessionCode)
    {
        // One thumbnail per hotel, so the join can never multiply rows
        $thumb = DB::table('hotels_images')
            ->select('hotel_code', DB::raw('MIN(thumbnail_image) as thumbnail_image'))
            ->groupBy('hotel_code');

        $hotels = DB::table('sessions_hotels_results as r')
            ->join('hotels as h', 'r.hotel_id', '=', 'h.hotel_code')
            ->leftJoinSub($thumb, 't', 't.hotel_code', '=', 'h.hotel_code')
            ->where('r.session_code', $sessionCode)
            ->select(
                'r.hotel_id',
                'r.price',
                'r.room_count',
                'r.amenities',
                'r.booking_key',
                'r.cancellation_policy',
                'h.hotel_name',
                'h.hotel_address',
                'h.city',
                'h.rating',
                DB::raw('COALESCE(t.thumbnail_image, "images/img5.jpg") as thumbnail')
            )
            ->orderBy('r.price')
            ->orderBy('r.id')
            ->get();

        $filterRatings    = [];
        $filterPrices     = [];
        $filterCities     = [];
        $filterBoardBasis = [];
        $filterRoomTypes  = [];
        $results          = [];

        foreach ($hotels as $hotel) {
            $amenities = json_decode($hotel->amenities, true) ?? [];

            if (is_string($amenities)) {
                $amenities = json_decode($amenities, true) ?? [];
            }

            $boardBasis = $amenities['boardbasis'] ?? [];
            $roomTypes  = $amenities['room_types'] ?? [];

            foreach ($boardBasis as $board) {
                $board = trim($board);
                if ($board !== '') {
                    $filterBoardBasis[strtolower($board)] = $board;
                }
            }

            foreach ($roomTypes as $roomType) {
                $roomType = trim($roomType);
                if ($roomType !== '') {
                    $filterRoomTypes[strtolower($roomType)] = $roomType;
                }
            }

            $filterRatings[$hotel->rating] = $hotel->rating;
            $filterCities[$hotel->city]    = $hotel->city;
            $filterPrices[$hotel->price]   = $hotel->price;

            $results[] = [
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
                'rooms'               => $amenities['rooms'] ?? [],
                'BookingKey'          => $hotel->booking_key,
                'cancellation_policy' => json_decode($hotel->cancellation_policy),
            ];
        }

        krsort($filterRatings);
        asort($filterPrices);
        asort($filterCities);
        asort($filterBoardBasis);
        asort($filterRoomTypes);

        return [
            'filters' => [
                'ratings'    => array_values($filterRatings),
                'prices'     => array_values($filterPrices),
                'cities'     => array_values($filterCities),
                'boardbasis' => array_values($filterBoardBasis),
                'roomtypes'  => array_values($filterRoomTypes),
            ],
            'hotels' => $results,
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
        $amount        = $data['amount']          ?? 0;

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
        $session_code    = $hotelSession->session_code;


        $roomsAdults = $hotelSession->rooms_adults ?? '[1]';
        if (is_string($roomsAdults)) {
            $roomsAdults = json_decode($roomsAdults, true) ?? [1];
        }
        if (!is_array($roomsAdults)) {
            $roomsAdults = [$roomsAdults];
        }

        $roomsChildren = $hotelSession->rooms_children ?? '[0]';
        if (is_string($roomsChildren)) {
            $roomsChildren = json_decode($roomsChildren, true) ?? [0];
        }
        if (!is_array($roomsChildren)) {
            $roomsChildren = [$roomsChildren];
        }

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
            $adultsCount   = $roomsAdults[$i]   ?? 1;
            $childrenCount = $roomsChildren[$i] ?? 0;
            $roomSize      = $adultsCount + $childrenCount;

            $roomTravellers = array_slice($travellers, $cursor, $roomSize);
            $cursor        += $roomSize;

            if (count($roomTravellers) !== $roomSize) {
                return [
                    'status'  => false,
                    'message' => "Room " . ($i + 1) . " expects {$roomSize} traveller(s) ({$adultsCount} adult(s), {$childrenCount} child(ren)) but received " . count($roomTravellers) . ".",
                ];
            }

            $guests = [];
            foreach ($roomTravellers as $index => $traveller) {
                $isChild = $index >= $adultsCount;
                $guests[] = [
                    'type'       => $isChild ? 'CHILD' : 'ADULT',
                    'first_name' => $traveller['first_name'] ?? '',
                    'last_name'  => $traveller['last_name']  ?? '',
                    'age'        => $isChild ? ($traveller['age'] ?? null) : null,
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

        $rezliveResult = $this->rezlive->processBooking($bookingCode, $bookingHotels, $session_code);

        if (!($rezliveResult['status'] ?? false)) {
            Log::error('Rezlive booking failed', [
                'booking_code' => $bookingCode,
                'error'        => $rezliveResult['message'] ?? 'Unknown error',
            ]);

            return [
                'status'  => false,
                'message' => 'Something went wrong please try again later',

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
                    // 'date_to'         => $arrivalDate,
                    // 'date_from'       => $departureDate,
                    'room_type'            => $room['room_type']   ?? null,
                    'nationality'          => $nationality,
                    'booking_key'          => $room['booking_key'] ?? null,
                    'amount'            =>  $amount,
                    'adults'         => $roomsAdults[$i]   ?? 1,
                    'children'       => $roomsChildren[$i] ?? 0,
                    'totalRates'           => $roomRate,
                    'totalRooms'     => $totalRoomCount,
                    'rezlivebookingId'   => $rezliveResult['booking_id']   ?? null,
                    'rezliveBookingCode' => $rezliveResult['booking_code'] ?? null,
                    'provider_booking_ref' => $rezliveResult['provider_ref'] ?? null,
                ]);
            }
        }
        Booking::create([
            'usercode' => $userCode,
            'booking_code' => $bookingCode,
            'booking_type' => 'Hotel',
            'booking_status' => 'Booked',
            'date_expiry' => Carbon::now()
        ]);
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
