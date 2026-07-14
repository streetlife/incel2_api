<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HotelSession;
use App\Models\RezliveLog;
use App\Services\HotelServices;
use App\Services\RezliveServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

set_time_limit(0);
ini_set('max_execution_time', 0);
class HotelController extends Controller
{
    protected HotelServices $hotelService;

    public function __construct(HotelServices $hotelService, protected RezliveServices $rezliveBooking)
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
    //   public function createBooking(Request $request)
    // {
    //     $request->validate([
    //         'session_code' => 'required|string',
    //         'hotel_id' => 'required',
    //         'country_code' => 'required|string',
    //         'city_code' => 'required|string',
    //         'arrival_date' => 'required|date',
    //         'departure_date' => 'required|date',
    //         // 'prebooking_file' => 'required|string'
    //     ]);

    //     $result = $this->hotelService->createHotelsBooking($request->all());

    //     return response()->json($result);
    // }
    // public function createBooking(Request $request)
    // {
    //     $request->validate([
    //         'session_code'   => 'required|string',
    //         'hotel_id'       => 'required',
    //         'hotel_name'     => 'required|string',
    //         'country_code'   => 'required|string',
    //         'city_code'      => 'required|string',
    //         'arrival_date'   => 'required|string',
    //         'departure_date' => 'required|string',
    //         'rooms_type'     => 'required|string',
    //         'rooms_key'      => 'required|string',
    //         'rooms_adults'   => 'required|integer|min:1',
    //         'rooms_children' => 'nullable|integer|min:0',
    //         'room_rates'     => 'required',

    //         // travellers array
    //         'travellers'              => 'required|array|min:1',
    //         'travellers.*.first_name' => 'required|string',
    //         'travellers.*.last_name'  => 'required|string',
    //         'travellers.*.title'      => 'required|string',
    //         'travellers.*.type'       => 'required|in:ADULT,CHILD',
    //         'travellers.*.age'        => 'nullable|integer',  // required if CHILD
    //     ]);

    //     $result = $this->hotelService->createHotelsBooking($request->all());

    //     $statusCode = $result['status'] ? 200 : 422;

    //     return response()->json($result, $statusCode);
    // }
    public function createBooking(Request $request)
    {
        $request->validate([
            'session_code'   => 'required|string',
            'hotel_id'       => 'required',
            'hotel_name'     => 'required|string',
            'country_code'   => 'required|string',
            'city_code'      => 'required|string',
            'arrival_date'   => 'required|string',
            'departure_date' => 'required|string',
            'rooms_type'     => 'required|string',
            'rooms_key'      => 'required|string',

            // per-room arrays
            'rooms_adults'            => 'required|array|min:1',
            'rooms_adults.*'          => 'required|integer|min:1',
            'rooms_children'          => 'nullable|array',
            'rooms_children.*'        => 'nullable|integer|min:0',

            'room_rates'     => 'required',

            'travellers'              => 'required|array|min:1',
            'travellers.*.first_name' => 'required|string',
            'travellers.*.last_name'  => 'required|string',
            'travellers.*.title'      => 'required|string',
            'travellers.*.type'       => 'required|in:ADULT,CHILD',
            'travellers.*.age'        => 'nullable|integer',
        ]);

        $result = $this->hotelService->createHotelsBooking($request->all());

        $statusCode = $result['status'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    public function fetchLog()
    {
        $logs = RezliveLog::latest()->get();

        return response()->json([
            'status' => true,
            'message' => 'Logs fetched successfully',
            'data' => $logs
        ]);
    }

    public function prebook(Request $request)
    {
        $rezlive = app(\App\Services\RezliveServices::class);
        // $session = HotelSession::where('session_code', $request->session_code)->first();

        // if (!$session) {
        //     return response()->json(['status' => false, 'message' => 'Session not found']);
        // }

        $roomsAdults       = $request->rooms_adults ?? [];
        $roomsChildren     = $request->rooms_children ?? [];
        $roomsChildrenAges = $request->rooms_children_ages ?? [];
        $totalRooms        = count($roomsAdults);

        $hotelData = [
            'search_session_id'   => $request->search_session_id,
            'arrival_date'        => $request->arrival_date,
            'departure_date'      => $request->departure_date,
            'nationality'         => $request->nationality,
            'country_code'        => $request->country_code,
            'city_code'           => $request->city_code,
            'hotel_id'            => $request->hotel_id,
            // 'hotel_name'          => $request->hotel_name,
            // 'hotel_address'       => $request->hotel_address,
            'currency'            => $request->currency,
            'rooms_adults'        => $roomsAdults,
            'rooms_children'      => $roomsChildren,
            'rooms_children_ages' => $roomsChildrenAges,
        ];

        $bookingHotels = [];
        for ($i = 0; $i < $totalRooms; $i++) {
            $bookingHotels[] = [
                'room_type'   => $request->room_type,
                'booking_key' => $request->booking_key,
                'total_rate'  => $request->total_rate,
                'guests'      => [],
            ];
        }
        $result = $rezlive->preBook($hotelData, $bookingHotels);

        if (!($result['status'] ?? false)) {
            return response()->json([
                'status'  => false,
                'message' => $result['message'] ?? 'PreBook failed',
            ], 422);
        }

        $preBooking        = $result['data']['PreBookingRequest']['PreBooking'] ?? [];
        $roomDetail         = $preBooking['RoomDetails']['RoomDetail'] ?? [];
        $preBookingDetails  = $result['data']['PreBookingDetails'] ?? [];

        // normalize single room -> array of rooms
        if (isset($roomDetail['BookingKey'])) {
            $roomDetail = [$roomDetail];
        }

        $responseData = [
            'searchSessionId' => $preBooking['SearchSessionId'] ?? null,
            'arrivalDate'      => $preBooking['ArrivalDate'] ?? null,
            'departureDate'    => $preBooking['DepartureDate'] ?? null,
            'hotelId'          => $preBooking['HotelId'] ?? null,
            'currency'         => $preBooking['Currency'] ?? null,

            'rooms' => array_map(function ($room) {
                return [
                    'type'       => $room['Type'] ?? null,
                    'bookingKey' => $room['BookingKey'] ?? null,
                    'adults'     => $room['Adults'] ?? null,
                    'children'   => $room['Children'] ?? null,
                    'totalRooms' => $room['TotalRooms'] ?? null,
                    'totalRate'  => $room['TotalRate'] ?? null,
                    'boardBasis' => $room['BoardBasis'] ?? null,
                    'terms'      => $room['TermsAndConditions'] ?? null,
                ];
            }, $roomDetail),

            'beforePrice'     => $preBookingDetails['BookingBeforePrice'] ?? null,
            'afterPrice'      => $preBookingDetails['BookingAfterPrice'] ?? null,
            'priceDifference' => $preBookingDetails['Difference'] ?? null,
        ];

        return response()->json([
            'status'  => true,
            'message' => 'PreBook successful',
            'data'    => $responseData,
        ], 200);
    }
    public function prebooks(Request $request)
    {
        $rezlive = app(\App\Services\RezliveServices::class);
        $session = HotelSession::where('session_code', $request->session_code)->first();

        if (!$session) {
            return response()->json(['status' => false, 'message' => 'Session not found']);
        }

        $roomsAdults       = json_decode($session->rooms_adults, true);
        $roomsChildren     = json_decode($session->rooms_children, true);
        $roomsChildrenAges = json_decode($session->rooms_children_ages, true) ?? [];
        $totalRooms        = count($roomsAdults);

        $totalRates = is_array($request->total_rate)
            ? $request->total_rate
            : explode('|', $request->total_rate);

        $roomTypes = is_array($request->room_type)
            ? $request->room_type
            : array_fill(0, $totalRooms, $request->room_type);

        $bookingKeys = is_array($request->booking_key)
            ? $request->booking_key
            : array_fill(0, $totalRooms, $request->booking_key);

        $hotelData = [
            'search_session_id'   => $request->search_session_id,
            'arrival_date'        => $request->arrival_date,
            'departure_date'      => $request->departure_date,
            'nationality'         => $request->nationality,
            'country_code'        => $request->country_code,
            'city_code'           => $request->city_code,
            'hotel_id'            => $request->hotel_id,
            'rooms_adults'        => $roomsAdults,
            'rooms_children'      => $roomsChildren,
            'rooms_children_ages' => $roomsChildrenAges,
        ];

        $bookingHotels = [];
        for ($i = 0; $i < $totalRooms; $i++) {
            $bookingHotels[] = [
                'room_type'   => $roomTypes[$i]   ?? $roomTypes[0],
                'booking_key' => $bookingKeys[$i] ?? $bookingKeys[0],
                'total_rate'  => $totalRates[$i]  ?? $totalRates[0],
                'guests'      => [],
            ];
        }

        return response()->json($rezlive->preBook($hotelData, $bookingHotels));
    }
}
