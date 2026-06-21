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

    public function search(array $params, $arrivalDate, $departureDate)
    {
        DB::beginTransaction();

        try {

            $sessionCode = Str::uuid()->toString();

            // $arrivalDateFormatted = date('d/m/Y', strtotime($arrivalDate));
            // $departureDateFormatted = date('d/m/Y', strtotime($departureDate));

            $result = $this->rezlive->searchHotels($params, $arrivalDate, $departureDate);

            // Log::info('Rezlive hotel search response', $result);

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
                'session_code' =>  $sessionCode,
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
                    'session_code' =>  $sessionCode,
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
                'session_code' =>  $sessionCode,
                'search_session_id' => $result['data']['SearchSessionId'] ?? null,

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
                'hotels' => $results['hotels'],
                'bookingKey' => $result['booking_keys']
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
        Log::info("filter", $filterBoardBasis);
        $results = [];

        foreach ($hotels as $hotel) {

            $amenities = json_decode($hotel->amenities, true) ?? [];
            Log::info("amenities", ["amenties" => $amenities]);
            $boardBasis = isset($amenities->boardbasis) ? $amenities->boardbasis : [];
            Log::info("boardBasis", $boardBasis);
            foreach ($boardBasis as $board) {

                $board = trim($board);

                if ($board !== '') {

                    // normalize to avoid duplicates like "Room Only" and "room only"
                    $key = strtolower($board);

                    $filterBoardBasis[$key] = $board;
                }
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
                'board_basis' => $boardBasis,
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
        $session = HotelSession::where('session_code', $sessionCode)->firstOrFail();

        $rooms = [];

        for ($i = 1; $i <= $session->rooms; $i++) {

            $rooms[] = [
                'type' => "Room-$i",
                'adults' => 2,
                'children' => 0,
                'child_ages' => []
            ];
        }

        $payload = [
            'arrival_date' => date('d/m/Y', strtotime($session->arrival_date)),
            'departure_date' => date('d/m/Y', strtotime($session->departure_date)),
            'country_code' => $session->country_code,
            'city_code' => $session->city_code,
            'nationality' => $session->nationality,
            'hotel_id' => $hotelId,
            'rooms' => $rooms
        ];

        $hotelApi = $this->rezlive->findHotelById($payload);

        $hotelInfo = $hotelApi['Hotels']['Hotel'] ?? [];

        $rooms = $hotelInfo['RoomDetails']['RoomDetail'] ?? [];

        return [
            'status' => true,
            'hotel_info' => $hotelInfo,
            'rooms' => $rooms
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
        $countryCode   = $data['country_code']    ?? null;
        $cityCode      = $data['city_code']       ?? null;
        $arrivalDate   = $data['arrival_date']    ?? null;
        $departureDate = $data['departure_date']  ?? null;
        $travellers    = $data['travellers']      ?? [];
        $roomType      = $data['rooms_type']      ?? null;
        $bookingKey    = $data['rooms_key']       ?? null;
        $totalRate     = $data['room_rates']      ?? 0;
        $hotelName     = $data['hotel_name']      ?? null;

        // $convertDepartureDate = $this->changeDateFormatHotel($departureDate);
        // $convertArrivalDate   = $this->changeDateFormatHotel($arrivalDate);
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
        $countryCode     = $hotelSession->currency     ?? $countryCode;
        $cityCode        = $hotelSession->currency_code         ?? $cityCode;

        $bookingCode = $this->createBooking($userCode, 'HOTEL');
        $markup      = $this->getMarkup('HOTEL');

        // room_type, booking_key and total_rate can each arrive as a pipe-delimited
        // string (one value per room) — the same shape room_rates already used
        $roomTypes   = is_array($roomType)   ? $roomType   : explode('|', (string) $roomType);
        $bookingKeys = is_array($bookingKey) ? $bookingKey : explode('|', (string) $bookingKey);
        $totalRates  = is_array($totalRate)  ? $totalRate  : explode('|', (string) $totalRate);

        $totalRoomCount = count($totalRates);

        // Slice the flat traveller list into per-room groups, in submission order,
        // using each room's adult + child count
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

        // Booking-level fields (shared across the whole stay) live on the first
        // room entry, since processBooking() reads $bookingHotels[0] as $hotelData
        $bookingHotels[0] = array_merge($bookingHotels[0], [
            'search_session_id' => $searchSessionId,
            'hotel_id'           => $hotelId,
            'hotel_name'         => $hotelName,
            'country_code'       => $countryCode,
            'city_code'          => $cityCode,
            'arrival_date'       => $arrivalDate,
            'departure_date'     => $departureDate,
            'nationality'        => $nationality,
            'rooms_adults'       => $roomsAdults,
            'rooms_children'     => $roomsChildren,
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
