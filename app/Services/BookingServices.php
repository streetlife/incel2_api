<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingFlights;
use App\Models\BookingHotel;
use App\Models\BookingHotelGuest;
use App\Models\BookingTour;
use App\Models\BookingVisa;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingServices extends FlightServices
{

    public function createBooking($usercode, $bookingType)
    {
        $bookingCode = 'B' . now()->format('ymdHis') . rand(10, 99);

        $booking = Booking::create([
            'usercode'       => $usercode,
            'booking_code'   => $bookingCode,
            'booking_type'   => $bookingType,
            'booking_status' => 'NEW',
            'date_expiry'    => Carbon::now()->addMinutes(config('booking.expiry', 60))
        ]);

        return $booking;
    }
    private function generateUserCode(): string
    {
        return 'temp' . now()->format('ymdHis') . rand(10, 99);
    }
    public function getBooking($bookingCode)
    {
        return Booking::with([
            'tours',
            'flights',
            'hotels',
            'visas'
        ])->where('booking_code', $bookingCode)->first();
    }
    public function createTourBooking(array $data)
    {
        $bookingDetailCode = 'BT' . now()->format('ymdHis') . rand(10, 99);

        $tour = BookingTour::create([
            'booking_code'       => $data['booking_code'],
            'booking_detail_code' => $bookingDetailCode,
            'traveller_code'     => '',
            'tour_id'            => $data['tour_id'],
            'travel_date'        => $data['travel_date'],
            'currency_code'      => $data['currency_code'],
            'amount'             => $data['amount'],
            'amount_display'     => $data['amount_display'],
            'status'             => 'NEW',
            'transfer_id'        => $data['transfer_id'] ?? null,
            'transfer_option'    => $data['transfer_option'] ?? null,
            'transfer_name'      => $data['transfer_name'] ?? null,
            'contract_id'        => $data['contract_id'] ?? null,
            'tour_option_id'     => $data['tour_option_id'] ?? null,
            'traveller_type'     => $data['traveller_type'] ?? null,
            'time_slot_id'       => $data['time_slot_id'] ?? 0,
            'time_slot_name'     => $data['time_slot_name'] ?? ''
        ]);

        return $tour;
    }

    public function createVisaBooking($bookingCode, $visaId)
    {
        return BookingVisa::create([
            'booking_code' => $bookingCode,
            'booking_detail_code' => 'BV' . now()->format('ymdHis') . rand(10, 99),
            'visa_id' => $visaId,
        ]);
    }
    public function createFlightBooking(
        $bookingCode,
        array $travelerPricing,
        $payload,
        $result,
        $amadeusClientRef
    ) {
        return DB::transaction(function () use (
            $bookingCode,
            $travelerPricing,
            $payload,
            $result,
            $amadeusClientRef
        ) {

            $resultDecoded = json_decode($result, true);

            $airline = $resultDecoded[0]['validatingAirlineCodes'][0] ?? null;

            $flightPrice = $travelerPricing['price']['total'];
            $basePrice   = $travelerPricing['price']['base'];

            $priceMarkup = $this->calculateFlightPrice(
                $airline,
                $basePrice,
                $flightPrice
            );

            BookingFlights::create([
                'booking_code' => $bookingCode,
                'booking_detail_code' => 'BF' . now()->format('ymdHis'), 
                'traveller_id' => $travelerPricing['travelerId'],
                'traveller_type' => $travelerPricing['travelerType'],
                'fare_option' => $travelerPricing['fareOption'],
                'price' => $flightPrice,
                'price_markup' => $priceMarkup,
                'flight_session' => $result,
                'payload' =>  is_array($payload) ? json_encode($payload) : $payload,
                'amadeus_client_ref' => $amadeusClientRef,
            ]);
        });
    }

    public function createHotelBooking(array $data)
    {
        return DB::transaction(function () use ($data) {

            $hotel = BookingHotel::create([
                'booking_code'        => $data['booking_code'],
                'booking_detail_code' => 'BH' . now()->format('ymdHis') . rand(10, 99),
                'hotel_id'            => $data['hotel_id'],
                'date_from'           => $data['arrival_date'],
                'date_to'             => $data['departure_date'],
                'currency_code'       => 'USD',
                'amount'              => $data['amount'],
                'amount_display'      => $data['amount_display'],
                'booking_key'         => $data['booking_key'],
                'traveller_title'     => '',
                'first_name'          => '',
                'last_name'           => '',
                'country_code'        => $data['country_code'],
                'city_code'           => $data['city_code'],
                'room_type'           => $data['room_type'],
                'nationality'         => $data['nationality'],
                'session_id'          => $data['session_id'],
                'adults'              => $data['adults'],
                'children'            => $data['children'],
                'totalRooms'          => $data['totalRooms'],
                'totalRates'          => $data['totalRates'],
            ]);

            return $hotel;
        });
    }
    public function createHotelGuest(
        $bookingDetailCode,
        $bookingCode,
        $guestType
    ) {
        return BookingHotelGuest::create([
            'guest_code'          => 'BG' . strtoupper(Str::random(8)),
            'booking_detail_code' => $bookingDetailCode,
            'booking_code'        => $bookingCode,
            'traveller_title'     => '',
            'first_name'          => '',
            'last_name'           => '',
            'guest_type'          => $guestType,
        ]);
    }
}
