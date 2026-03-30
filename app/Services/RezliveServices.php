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
}