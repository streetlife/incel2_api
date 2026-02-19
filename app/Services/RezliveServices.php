<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RezliveServices {
    protected $url;
    protected $agent;
    protected $username;
    protected $password;
    protected $content_url;

    public function __construct() {
        $this->url = config("rezlive.rezlive_url");
        $this->agent = config("rezlive.rezlive_agent_code");
        $this->username = config("rezlive.rezlive_username");
        $this->password = config("rezlive.relive_password");
        $this->content_url = config("rezlive.content_url");
        
    
    }
     public function searchHotels(
        array $params,
        string $arrivalDate,
        string $departureDate
    ): array {

        $xml = $this->buildSearchXml($params, $arrivalDate, $departureDate);

        Log::info('Rezlive Request', ['xml' => $xml]);

        $response = Http::asForm()->post(
            $this->url . 'findhotel',
            ['XML' => $xml]
        );

        $xmlResponse = simplexml_load_string(
            $response->body(),
            'SimpleXMLElement',
            LIBXML_NOCDATA
        );

        $result = json_decode(json_encode($xmlResponse), true);

        Log::info('Rezlive Response', $result);

        return $result;
    }

    private function buildSearchXml(
        array $params,
        string $arrivalDate,
        string $departureDate
    ): string {

        $roomNumber = $params['room_number'];
        $childAges = $params['child'] ?? [];
        $childIndex = 0;

        $xml = "<HotelFindRequest>
        <Authentication>
            <AgentCode>{$this->agent}</AgentCode>
            <UserName>{$this->username}</UserName>
            <Password>{$this->password}</Password>
        </Authentication>
        <Booking>
            <ArrivalDate>{$arrivalDate}</ArrivalDate>
            <DepartureDate>{$departureDate}</DepartureDate>
            <CountryCode>{$params['search_hotel_country']}</CountryCode>
            <City>{$params['search_hotel_city']}</City>
            <GuestNationality>{$params['search_hotel_nationality']}</GuestNationality>
            <Rooms>";

        for ($room = 1; $room <= $roomNumber; $room++) {

            $adults = $params["room{$room}_adults"];
            $children = $params["room{$room}_children"];

            $xml .= "<Room>
                <Type>Room-{$room}</Type>
                <NoOfAdults>{$adults}</NoOfAdults>
                <NoOfChilds>{$children}</NoOfChilds>";

            if ($children > 0) {
                $xml .= "<ChildrenAges>";
                for ($i = 0; $i < $children; $i++) {
                    $xml .= "<ChildAge>{$childAges[$childIndex]}</ChildAge>";
                    $childIndex++;
                }
                $xml .= "</ChildrenAges>";
            }

            $xml .= "</Room>";
        }

        $xml .= "</Rooms>
        </Booking>
        </HotelFindRequest>";

        return $xml;
    }
}