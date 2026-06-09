<?php

namespace App\Services;

use App\Models\RezliveLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RezliveServices
{
    protected $url;
    protected $agent;
    protected $username;
    protected $password;
    protected $apiKey;

    private const LOG_DISK = 'xml_logs';

    public function __construct()
    {
        $this->url      = rtrim(config("rezlive.rezlive_url"), '/');
        $this->agent    = config("rezlive.rezlive_agent_code");
        $this->username = config("rezlive.rezlive_username");
        $this->password = config("rezlive.rezlive_password");
        $this->apiKey   = config("rezlive.rezlive_api_key");
    }


    // XML FILE LOGGER
    // -------------------------------------------------------------------------

    /**
     * Save XML request/response to storage/app/xml_logs/{type}/{timestamp}-{label}.xml
     *
     * @param string $type    e.g. 'search', 'find_by_id', 'prebook', 'booking'
     * @param string $label   e.g. 'request', 'response'
     * @param string $content XML string to save
     */

    private function saveXmlLog(string $type, string $label, string $content): void
    {
        try {
            $timestamp = now()->format('Ymd_His');
            $filename  = "xml_logs/{$timestamp}_{$label}_{$type}.xml";
            $path = public_path($filename);
            if (!file_exists(public_path('xml_logs'))) {
                mkdir(public_path('xml_logs'), 0755, true);
            }
            file_put_contents($path, $content);
            Log::Info("XML log saved", ['file' => $filename]);
        } catch (\Throwable $e) {
            Log::warning("Failed to save XML log", [
                'type'  => $type,
                'label' => $label,
                'error' => $e->getMessage(),
            ]);
        }
    }
    // -------------------------------------------------------------------------
    // SEARCH HOTELS
    // -------------------------------------------------------------------------

    public function searchHotels(array $params, string $arrivalDate, string $departureDate): array
    {
        Log::info("Rezlive Search Request", [
            'params'        => $params,
            'arrivalDate'   => $arrivalDate,
            'departureDate' => $departureDate,
        ]);

        try {
            $xml      = $this->buildSearchXml($params, $arrivalDate, $departureDate);
            $endpoint = $this->url . "/findhotel";

            $this->saveXmlLog('search', 'request', $xml);

            Log::info("Rezlive Request XML", ['xml' => $xml]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
                'x-api-key'    => $this->apiKey,
            ])->asForm()->post($endpoint, ['XML' => $xml]);

            $body = $response->body();

            $this->saveXmlLog('search', 'response', $body);

            RezliveLog::create([
                'type'             => 'hotel',
                'request_xml'      => $xml,
                'response_xml'     => $body,
                'request_payload'  => [
                    'params'        => $params,
                    'arrivalDate'   => $arrivalDate,
                    'departureDate' => $departureDate,
                ],
                'status_code'      => $response->status(),
            ]);

            Log::info("Rezlive Raw Response", [
                'status' => $response->status(),
                'body'   => $body,
            ]);

            if ($response->failed()) {
                return ['status' => false, 'message' => 'Rezlive request failed'];
            }

            libxml_use_internal_errors(true);
            $xmlResponse = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($xmlResponse === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                return ['status' => false, 'message' => 'Invalid XML response', 'errors' => $errors];
            }

            $result      = json_decode(json_encode($xmlResponse), true);
            $bookingKeys = [];

            foreach ($result['Hotels']['Hotel'] as $hotel) {
                $rooms = $hotel['RoomDetails']['RoomDetail'] ?? [];

                if (isset($rooms['BookingKey'])) {
                    $rooms = [$rooms];
                }

                foreach ($rooms as $room) {
                    $bookingKeys[] = [
                        'hotel_id'    => $hotel['Id'],
                        'hotel_name'  => $hotel['Name'],
                        'booking_key' => $room['BookingKey'],
                    ];
                }
            }

            Log::info("result-k", $result);

            return [
                'status'       => true,
                'message'      => 'Success',
                'data'         => $result,
                'booking_keys' => $bookingKeys ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Rezlive Error', ['message' => $e->getMessage()]);
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // FIND HOTEL BY ID
    // -------------------------------------------------------------------------

    public function findHotelById($payload): array
    {
        $xml      = $this->buildXml($payload);
        $endpoint = $this->url . "/findhotelbyid";

        $this->saveXmlLog('find_by_id', 'request', $xml);

        Log::info('Rezlive Request', ['xml' => $xml]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
            'x-api-key'    => $this->apiKey,
        ])->asForm()->post($endpoint, ['XML' => $xml]);

        $body = $response->body();

        $this->saveXmlLog('find_by_id', 'response', $body);

        Log::info("Rezlive Response", ['body' => $body]);

        $data = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);

        return json_decode(json_encode($data), true);
    }

    // -------------------------------------------------------------------------
    // PROCESS BOOKING
    // -------------------------------------------------------------------------

    public function processBooking($bookingCode, $bookingHotels): array
    {
        Log::info('Processing Hotel Booking', ['booking_code' => $bookingCode]);

        $hotelData = $bookingHotels[0];

        // Pre-book
        $preBookResponse = $this->preBook($hotelData, $bookingHotels);

        if (!$preBookResponse || !empty($preBookResponse['error'])) {
            return [
                'status'  => false,
                'message' => $preBookResponse['error'] ?? 'Pre-booking failed',
            ];
        }

        Log::info('Rezlive Pre-Booking Success', $preBookResponse);

        // Actual booking
        $xml      = $this->buildBookingXml($hotelData, $bookingHotels);
        $endpoint = $this->url . "/bookhotel";


        $this->saveXmlLog('booking', 'request', $xml);

        Log::info('Rezlive Booking XML', ['xml' => $xml]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
            'x-api-key'    => $this->apiKey,
        ])->asForm()->post($endpoint, ['XML' => $xml]);

        $body = trim($response->body());


        $this->saveXmlLog('booking', 'response', $body);

        Log::info('Rezlive Booking Response', ['response' => $body]);

        if (empty($body) || stripos($body, '<html') !== false) {
            return ['status' => false, 'message' => 'Booking returned invalid response from provider'];
        }

        libxml_use_internal_errors(true);
        $xmlResponse = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($xmlResponse === false) {
            libxml_clear_errors();
            return ['status' => false, 'message' => 'Failed to parse booking response'];
        }

        libxml_clear_errors();

        $responseJson = json_decode(json_encode($xmlResponse), true);

        return $this->handleBookingResponse($bookingCode, $responseJson, $body);
    }



    private function preBook($hotelData, $bookingHotels): array
    {
        $xml      = $this->buildBookingXml($hotelData, $bookingHotels);
        $endpoint = $this->url . "/bookhotel";


        $this->saveXmlLog('prebook', 'request', $xml);

        Log::info('Rezlive Pre-Booking Request', ['endpoint' => $endpoint, 'xml' => $xml]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
            'x-api-key'    => $this->apiKey,
        ])->asForm()->post($endpoint, ['XML' => $xml]);

        $body = trim($response->body());


        $this->saveXmlLog('prebook', 'response', $body);

        Log::info('Rezlive Pre-Booking Response', ['response' => $body]);

        if (empty($body)) {
            Log::error('Rezlive Pre-Booking: Empty response', ['endpoint' => $endpoint]);
            return ['error' => 'Pre-booking returned an empty response from provider'];
        }

        if (stripos($body, '<!DOCTYPE') !== false || stripos($body, '<html') !== false) {
            Log::error('Rezlive Pre-Booking: HTML error page returned', ['body' => $body]);
            return ['error' => 'Pre-booking endpoint returned an HTML page — check the API URL'];
        }

        if (strpos($body, '<?xml') === false && strpos($body, '<') !== 0) {
            Log::error('Rezlive Pre-Booking: Non-XML response', ['body' => $body]);
            return ['error' => 'Pre-booking returned an unexpected response format'];
        }

        libxml_use_internal_errors(true);
        $xmlResponse = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($xmlResponse === false) {
            $errors        = libxml_get_errors();
            libxml_clear_errors();
            $errorMessages = array_map(fn($e) => trim($e->message), $errors);

            Log::error('Rezlive Pre-Booking: XML parse failed', [
                'errors' => $errorMessages,
                'body'   => $body,
            ]);

            return ['error' => 'Failed to parse pre-booking response: ' . implode(', ', $errorMessages)];
        }

        libxml_clear_errors();

        return json_decode(json_encode($xmlResponse), true);
    }


    //     private function buildSearchXml(array $params, string $arrivalDate, string $departureDate): string
    //     {
    //         $arrivalDate   = Carbon::parse($arrivalDate)->format('Y-m-d');
    //         $departureDate = Carbon::parse($departureDate)->format('Y-m-d');
    //         Log::info("Rezlive Search Params", [
    //             'params'        => $params,
    //             'arrivalDate'   => $arrivalDate,
    //             'departureDate' => $departureDate,
    //         ]);

    //         $country     = $params['search_hotel_country'];
    //         $city        = $params['search_hotel_city'];
    //         $nationality = $params['search_hotel_nationality'];
    //         $roomNumber  = $params['room_number'] ?? 1;
    //         $childAges   = $params['child'] ?? [];
    //         $arrivalDate =  $arrivalDate;
    //         $departureDate =  $departureDate;
    //         $childIndex  = 0;

    //         $xml = "<HotelFindRequest>
    // <Authentication>
    // <AgentCode>{$this->agent}</AgentCode>
    // <UserName>{$this->username}</UserName>
    // </Authentication>
    // <Booking>
    // <ArrivalDate>{$arrivalDate}</ArrivalDate>
    // <DepartureDate>{$departureDate}</DepartureDate>
    // <CountryCode>{$country}</CountryCode>
    // <City>{$city}</City>
    // <GuestNationality>{$nationality}</GuestNationality>
    // <HotelRatings>
    // <HotelRating>1</HotelRating>
    // <HotelRating>2</HotelRating>
    // <HotelRating>3</HotelRating>
    // <HotelRating>4</HotelRating>
    // <HotelRating>5</HotelRating>
    // </HotelRatings>
    // <Rooms>";

    //         for ($room = 1; $room <= $roomNumber; $room++) {
    //             $adults   = $params["room{$room}_adults"] ?? 2;
    //             $children = $params["room{$room}_children"] ?? 0;

    //             $xml .= "<Room>
    // <Type>Room-{$room}</Type>
    // <NoOfAdults>{$adults}</NoOfAdults>
    // <NoOfChilds>{$children}</NoOfChilds>";

    //             if ($children > 0) {
    //                 $xml .= "<ChildrenAges>";
    //                 for ($i = 0; $i < $children; $i++) {
    //                     if (isset($childAges[$childIndex])) {
    //                         $xml .= "<ChildAge>{$childAges[$childIndex]}</ChildAge>";
    //                     }
    //                     $childIndex++;
    //                 }
    //                 $xml .= "</ChildrenAges>";
    //             }

    //             $xml .= "</Room>";
    //         }

    //         $xml .= "</Rooms></Booking></HotelFindRequest>";

    //         return $xml;
    //     }

    private function buildSearchXml(array $params, string $arrivalDate, string $departureDate): string
    {
        // $arrivalDate   = Carbon::parse($arrivalDate)->format('Y-m-d');
        // $departureDate = Carbon::parse($departureDate)->format('Y-m-d');

        Log::info("Rezlive Search Params", [
            'params'        => $params,
            'arrivalDate'   => $arrivalDate,
            'departureDate' => $departureDate,
        ]);

        $country     = $params['search_hotel_country'];
        $city        = $params['search_hotel_city'];
        $nationality = $params['search_hotel_nationality'];
        $roomNumber  = $params['room_number'] ?? 1;
        $childAges   = $params['child'] ?? [];
        $childIndex  = 0;

        $xml = "<HotelFindRequest>
<Authentication>
<AgentCode>{$this->agent}</AgentCode>
<UserName>{$this->username}</UserName>
</Authentication>
<Booking>
<ArrivalDate>{$arrivalDate}</ArrivalDate>
<DepartureDate>{$departureDate}</DepartureDate>
<CountryCode>{$country}</CountryCode>
<City>{$city}</City>
<GuestNationality>{$nationality}</GuestNationality>
<HotelRatings>
<HotelRating>1</HotelRating>
<HotelRating>2</HotelRating>
<HotelRating>3</HotelRating>
<HotelRating>4</HotelRating>
<HotelRating>5</HotelRating>
</HotelRatings>
<Rooms>";

        for ($room = 1; $room <= $roomNumber; $room++) {
            $adults   = $params["room{$room}_adults"] ?? 2;
            $children = $params["room{$room}_children"] ?? 0;

            $xml .= "<Room>
<Type>Room-{$room}</Type>
<NoOfAdults>{$adults}</NoOfAdults>
<NoOfChilds>{$children}</NoOfChilds>";

            if ($children > 0) {
                $xml .= "<ChildrenAges>";
                for ($i = 0; $i < $children; $i++) {
                    if (isset($childAges[$childIndex])) {
                        $xml .= "<ChildAge>{$childAges[$childIndex]}</ChildAge>";
                    }
                    $childIndex++;
                }
                $xml .= "</ChildrenAges>";
            }

            $xml .= "</Room>";
        }

        $xml .= "</Rooms></Booking></HotelFindRequest>";

        return $xml;
    }
    protected function buildXml($payload): string
    {
        $arrivalDate   = $payload['arrival_date'];
        $departureDate = $payload['departure_date'];
        $roomsXml = '';

        foreach ($payload['rooms'] as $index => $room) {
            $roomsXml .= "<Room>
                <Type>Room-" . ($index + 1) . "</Type>
                <NoOfAdults>{$room['adults']}</NoOfAdults>
                <NoOfChilds>{$room['children']}</NoOfChilds>";

            if ($room['children'] > 0) {
                $roomsXml .= "<ChildrenAges>";
                foreach ($room['child_ages'] ?? [] as $age) {
                    $roomsXml .= "<ChildAge>{$age}</ChildAge>";
                }
                $roomsXml .= "</ChildrenAges>";
            }

            $roomsXml .= "</Room>";
        }

        return "
        <HotelFindRequest>
            <Authentication>
              <AgentCode>{$this->agent}</AgentCode>
              <UserName>{$this->username}</UserName>
            </Authentication>
            <Booking>
                <ArrivalDate>{$arrivalDate}</ArrivalDate>
                <DepartureDate>{$departureDate}</DepartureDate>
                <CountryCode>{$payload['country_code']}</CountryCode>
                <City>{$payload['city_code']}</City>
                <HotelIDs>
                    <Int>{$payload['hotel_id']}</Int>
                </HotelIDs>
                <GuestNationality>{$payload['nationality']}</GuestNationality>
                <Rooms>{$roomsXml}</Rooms>
            </Booking>
        </HotelFindRequest>";
    }

    private function buildBookingXml($hotelData, $bookingHotels): string
    {
        $arrivalDate   = $hotelData['arrival_date'];
        $departureDate = $hotelData['departure_date'];
        $childrenAges    = '';
        $guestsXml       = '';
        $totalRates      = [];
        $adultsCount     = $hotelData['rooms_adults'];
        $childrenCount   = $hotelData['rooms_children'];
        $searchSessionId = (string) $hotelData['search_session_id'];

        foreach ($bookingHotels as $hotel) {
            $guestsXml .= "<Guests>";

            foreach ($hotel['guests'] as $guest) {
                if ($guest['type'] === 'ADULT') {
                    $guestsXml .= "
                <Guest>
                    <Salutation>MR</Salutation>
                    <FirstName>" . htmlspecialchars($guest['first_name']) . "</FirstName>
                    <LastName>" . htmlspecialchars($guest['last_name']) . "</LastName>
                </Guest>";
                }

                if ($guest['type'] === 'CHILD') {
                    $guestsXml .= "
                <Guest>
                    <Salutation>Child</Salutation>
                    <FirstName>" . htmlspecialchars($guest['first_name']) . "</FirstName>
                    <LastName>" . htmlspecialchars($guest['last_name']) . "</LastName>
                    <IsChild>1</IsChild>
                    <Age>{$guest['age']}</Age>
                </Guest>";
                    $childrenAges .= $guest['age'] . '*';
                }
            }

            $guestsXml   .= "</Guests>";
            $totalRates[] = $hotel['total_rate'] ?? 0;
        }

        $childrenAges = rtrim($childrenAges, '*');
        $totalRateStr = implode('|', $totalRates);
        $totalRooms   = count($bookingHotels);

        return "
    <BookingRequest>
        <Authentication>
            <AgentCode>{$this->agent}</AgentCode>
            <UserName>{$this->username}</UserName>
        </Authentication>
        <Booking>
            <SearchSessionId>{$searchSessionId}</SearchSessionId>
            <AgentRefNo>{$this->agent}</AgentRefNo>
            <ArrivalDate>{$arrivalDate}</ArrivalDate>
            <DepartureDate>{$departureDate}</DepartureDate>
            <GuestNationality>{$hotelData['nationality']}</GuestNationality>
            <CountryCode>{$hotelData['country_code']}</CountryCode>
            <City>{$hotelData['city_code']}</City>
            <HotelId>{$hotelData['hotel_id']}</HotelId>
            <Name>" . htmlspecialchars($hotelData['hotel_name']) . "</Name>
            <Currency>USD</Currency>
            <RoomDetails>
                <RoomDetail>
                    <Type>" . htmlspecialchars($hotelData['room_type']) . "</Type>
                    <BookingKey>{$hotelData['booking_key']}</BookingKey>
                    <Adults>{$adultsCount}</Adults>
                    <Children>{$childrenCount}</Children>
                    <ChildrenAges>{$childrenAges}</ChildrenAges>
                    <TotalRooms>{$totalRooms}</TotalRooms>
                    <TotalRate>{$totalRateStr}</TotalRate>
                    {$guestsXml}
                </RoomDetail>
            </RoomDetails>
        </Booking>
    </BookingRequest>";
    }



    private function handleBookingResponse($bookingCode, $responseJson, $rawResponse): array
    {
        $bookingStatus  = $responseJson['BookingDetails']['BookingStatus'] ?? null;
        $bookingId      = $responseJson['BookingDetails']['BookingId'] ?? null;
        $bookingCodeApi = $responseJson['BookingDetails']['BookingCode'] ?? null;

        if ($bookingStatus === 'Confirmed') {
            return [
                'status'       => true,
                'message'      => 'Booking successful',
                'booking_id'   => $bookingId,
                'booking_code' => $bookingCodeApi,
            ];
        }

        return [
            'status'   => false,
            'message'  => 'Booking failed',
            'response' => $rawResponse,
        ];
    }
}
