<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RezliveServices
{
    protected $url;
    protected $agent;
    protected $username;
    protected $password;
    protected $apiKey;

    public function __construct()
    {
        $this->url      = rtrim(config("rezlive.rezlive_url"), '/');
        $this->agent    = config("rezlive.rezlive_agent_code");
        $this->username = config("rezlive.rezlive_username");
        $this->password = config("rezlive.rezlive_password");
        $this->apiKey   = config("rezlive.rezlive_api_key");
    }

    /**
     * Search Hotels
     */
    public function searchHotels(array $params, string $arrivalDate, string $departureDate): array
    {
        Log::info("Rezlive Search Request", ['params' => $params, 'arrivalDate' => $arrivalDate, 'departureDate' => $departureDate]);
        try {

            $xml = $this->buildSearchXml($params, $arrivalDate, $departureDate);

            Log::info("Rezlive Request XML", ['xml' => $xml]);

            $endpoint = $this->url . "/findhotel";

            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
                'x-api-key' => $this->apiKey
            ])->asForm()->post($endpoint, [
                'XML' => $xml
            ]);

            $body = $response->body();

            Log::info("Rezlive Raw Response", [
                'status' => $response->status(),
                'body' => $body
            ]);

            if ($response->failed()) {
                return [
                    'status' => false,
                    'message' => 'Rezlive request failed'
                ];
            }

            libxml_use_internal_errors(true);

            $xmlResponse = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($xmlResponse === false) {

                $errors = libxml_get_errors();

                libxml_clear_errors();

                return [
                    'status' => false,
                    'message' => 'Invalid XML response',
                    'errors' => $errors
                ];
            }

            $result = json_decode(json_encode($xmlResponse), true);

            return [
                'status' => true,
                'message' => 'Success',
                'data' => $result
            ];
        } catch (\Exception $e) {

            Log::error('Rezlive Error', [
                'message' => $e->getMessage()
            ]);

            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Build XML Request
     */
    private function buildSearchXml(array $params, string $arrivalDate, string $departureDate): string
    {
        // $arrivalDate   = date('d/m/Y', strtotime($arrivalDate));
        // $departureDate = date('d/m/Y', strtotime($departureDate));
        Log::info("Rezlive Search Params", ['params' => $params, 'arrivalDate' => $arrivalDate, 'departureDate' => $departureDate]);
        $country     = $params['search_hotel_country'];
        $city        = $params['search_hotel_city'];
        $nationality = $params['search_hotel_nationality'];

        $roomNumber = $params['room_number'] ?? 1;

        $childAges  = $params['child'] ?? [];
        $childIndex = 0;

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

                        $age = $childAges[$childIndex];

                        $xml .= "<ChildAge>{$age}</ChildAge>";
                    }

                    $childIndex++;
                }

                $xml .= "</ChildrenAges>";
            }

            $xml .= "</Room>";
        }

        $xml .= "

</Rooms>

</Booking>

</HotelFindRequest>";

        return $xml;
    }
    public function findHotelById($payload)
    {
        $xml = $this->buildXml($payload);

        Log::info('Rezlive Request', ['xml' => $xml]);

        $endpoint = $this->url . "/findhotelbyid";

        $response = Http::withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
            'x-api-key' => $this->apiKey
        ])->asForm()->post($endpoint, [
            'XML' => $xml
        ]);

        Log::info('Rezlive Response', ['response' => $response->body()]);

        $data = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);

        return json_decode(json_encode($data), true);
    }
    protected function buildXml($payload)
    {
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
                <ArrivalDate>{$payload['arrival_date']}</ArrivalDate>
                <DepartureDate>{$payload['departure_date']}</DepartureDate>
                <CountryCode>{$payload['country_code']}</CountryCode>
                <City>{$payload['city_code']}</City>

                <HotelIDs>
                    <Int>{$payload['hotel_id']}</Int>
                </HotelIDs>

                <GuestNationality>{$payload['nationality']}</GuestNationality>

                <Rooms>
                    {$roomsXml}
                </Rooms>

            </Booking>

        </HotelFindRequest>
        ";
    }
    public function processBooking($bookingCode, $bookingHotels)
    {
        Log::info('Processing Hotel Booking', ['booking_code' => $bookingCode]);

        $hotelData = $bookingHotels[0];

        $xml = $this->buildBookingXml($hotelData, $bookingHotels);

        Log::info('Rezlive Booking Request', ['xml' => $xml]);

        $endpoint = $this->url . "/bookhotel";

        $response = Http::withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
            'x-api-key' => $this->apiKey
        ])->asForm()->post($endpoint, [
            'XML' => $xml
        ]);

        Log::info('Rezlive Booking Response', [
            'response' => $response->body()
        ]);

        $xmlResponse = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);

        $responseJson = json_decode(json_encode($xmlResponse), true);

        return $this->handleBookingResponse($bookingCode, $responseJson, $response->body());
    }

    private function buildBookingXml($hotelData, $bookingHotels)
    {
        $childrenAges = '';
        $guestsXml = '';

        foreach ($bookingHotels as $hotel) {

            $guestsXml .= "<Guests>";

            foreach ($hotel['guests'] as $guest) {

                if ($guest['type'] === 'ADULT') {

                    $guestsXml .= "
                    <Guest>
                        <Salutation>MR</Salutation>
                        <FirstName>{$guest['first_name']}</FirstName>
                        <LastName>{$guest['last_name']}</LastName>
                    </Guest>";
                }

                if ($guest['type'] === 'CHILD') {

                    $guestsXml .= "
                    <Guest>
                        <Salutation>Child</Salutation>
                        <FirstName>{$guest['first_name']}</FirstName>
                        <LastName>{$guest['last_name']}</LastName>
                        <IsChild>1</IsChild>
                        <Age>{$guest['age']}</Age>
                    </Guest>";

                    $childrenAges .= $guest['age'] . '*';
                }
            }

            $guestsXml .= "</Guests>";
        }

        $childrenAges = rtrim($childrenAges, '*');

        return "

        <BookingRequest>

            <Authentication>
              <AgentCode>{$this->agent}</AgentCode>
              <UserName>{$this->username}</UserName>
            </Authentication>

            <Booking>

                <SearchSessionId>{$hotelData['search_session_id']}</SearchSessionId>

                <AgentRefNo>{$this->agent}</AgentRefNo>

                <ArrivalDate>{$hotelData['arrival_date']}</ArrivalDate>
                <DepartureDate>{$hotelData['departure_date']}</DepartureDate>

                <GuestNationality>{$hotelData['nationality']}</GuestNationality>

                <CountryCode>{$hotelData['country_code']}</CountryCode>
                <City>{$hotelData['city_code']}</City>

                <HotelId>{$hotelData['hotel_id']}</HotelId>

                <Name>{$hotelData['hotel_name']}</Name>

                <Currency>USD</Currency>

                <RoomDetails>

                    <RoomDetail>

                        <Type>{$hotelData['room_type']}</Type>

                        <BookingKey>{$hotelData['booking_key']}</BookingKey>

                        <Adults>{$hotelData['adults']}</Adults>

                        <Children>{$hotelData['children']}</Children>

                        <ChildrenAges>{$childrenAges}</ChildrenAges>

                        <TotalRooms>{$hotelData['totalRooms']}</TotalRooms>

                        <TotalRate>{$hotelData['totalRates']}</TotalRate>

                        {$guestsXml}

                    </RoomDetail>

                </RoomDetails>

            </Booking>

        </BookingRequest>
        ";
    }

    private function handleBookingResponse($bookingCode, $responseJson, $rawResponse)
    {
        $bookingStatus = $responseJson['BookingDetails']['BookingStatus'] ?? null;
        $bookingId = $responseJson['BookingDetails']['BookingId'] ?? null;
        $bookingCodeApi = $responseJson['BookingDetails']['BookingCode'] ?? null;

        if ($bookingStatus === 'Confirmed') {
            return [
                'status' => true,
                'message' => 'Booking successful',
                'booking_id' => $bookingId,
                'booking_code' => $bookingCodeApi
            ];
        }

        return [
            'status' => false,
            'message' => 'Booking failed',
            'response' => $rawResponse
        ];
    }
}
