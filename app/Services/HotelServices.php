<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\HotelAmenity;
use App\Models\HotelCities;
use App\Models\HotelCountry;
use App\Models\HotelRoomAmenity;
use App\Models\HotelSession;
use App\Models\HotelSessionResult;
use Illuminate\Support\Facades\DB;
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

    public function search(array $params): array
    {
        DB::beginTransaction();

        try {

            $sessionCode = Str::uuid()->toString();
            $arrivalDate = date('d/m/Y', strtotime(substr($params['daterange'], 0, 10)));
            $departureDate = date('d/m/Y', strtotime(substr($params['daterange'], 13, 10)));
            $result = $this->rezlive->searchHotels(
                $params,
                $arrivalDate,
                $departureDate
            );

            if (isset($result['error'])) {
                throw new \Exception($result['error']);
            }

            if (isset($result['Hotels']['Error'])) {
                throw new \Exception('No result returned from supplier.');
            }

            $hotels = $result['Hotels']['Hotel'] ?? [];
            $hotelCount = count($hotels);

            HotelSession::create([
                'session_code' => $sessionCode,
                'country_code' => $params['search_hotel_country'],
                'city_code' => $params['search_hotel_city'],
                'arrival_date' => $arrivalDate,
                'departure_date' => $departureDate,
                'currency' => $result['Currency'] ?? 'USD',
                'currency_code' => $result['Currency'] ?? 'USD',
                'result_count' => $hotelCount,
                'rooms' => $params['room_number'],
                'adults' => max(1, $params['adult_number']),
                'children' => $params['child_number'] ?? 0,
                'nationality' => $params['search_hotel_nationality'],
                'search_session_id' => $result['SearchSessionId'] ?? null
            ]);


            foreach ($hotels as $hotel) {

                if (empty($hotel['Id'])) continue;

                $rooms = $hotel['RoomDetails']['RoomDetail'] ?? [];
                $boards = [];

                foreach ($rooms as $room) {
                    $boards[$room['BoardBasis']] = $room['BoardBasis'];
                }

                $amenities = [
                    'roomcount' => count($rooms),
                    'boardbasis' => $boards
                ];

                HotelSessionResult::create([
                    'session_code' => $sessionCode,
                    'hotel_id' => $hotel['Id'],
                    'hotel_rating' => $hotel['Rating'],
                    'hotel_thumbs' => '-',
                    'price' => $hotel['Price'],
                    'room_count' => $hotel['Hotelwiseroomcount'],
                    'amenities' => json_encode($amenities)
                ]);
            }

            DB::commit();

            return [
                'status' => true,
                'session_code' => $sessionCode,
                'result_count' => $hotelCount
            ];
        } catch (\Throwable $e) {

            DB::rollBack();

            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
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
}
