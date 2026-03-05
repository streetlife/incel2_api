<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingFlights;
use App\Models\BookingHotel;
use App\Models\BookingHotelGuest;
use App\Models\BookingTour;
use App\Models\BookingVisa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingServices extends FlightServices
{
    protected $amadeusService;

    public function __construct(AmadeusServices $amadeusService)
    {
        $this->amadeusService = $amadeusService;
    }
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

    // public function createVisaBooking($data)
    // {
    //     $userCode = auth()->user()->usercode ?? "temp" . now()->format('ymdHis');
    //     $genrateBookingCode = $this->createBooking($userCode, 'VISA');
    //     $bookingDetailCode = 'BT' . now()->format('ymdHis');
    //     return BookingVisa::create([
    //         'booking_code' => $genrateBookingCode['booking_code'],
    //         'booking_detail_code'     => $bookingDetailCode,
    //         'surname'                 => $data['lastname'],
    //         'firstname'               => $data['firstname'],
    //         'othernames'              => $data['othernames'],
    //         'passport_expiry_date'    => $data['passport_expiry_date'],
    //         'passport_country'        => $data['passport_country'],
    //         'passport_number'         => $data['passport_number'],
    //         'passport_issuance_date'  => $data['passport_issuance_date'],
    //         'emailaddress'            => $data['email_address'],
    //         'birth_date'              => $data['birthdate'],
    //         'document_passport_photo' => $data['passport_photo_name'],
    //         'document_data_page'      => $data['passport_data_page_name'],
    //         'status'                  => 'NEW',
    //     ]);
    // }
    public function createVisaBooking(Request $request): array
    {
        $data = $request->validated();

        // $passportPhotoPath = $this->uploadFile(
        //     $request->file('passport_photo'),
        //     'visa/passport_photos'
        // );

        // $passportDataPagePath = $this->uploadFile(
        //     $request->file('passport_data_page'),
        //     'visa/passport_data_pages'
        // );

        $bookingDetailCode = 'BV' . now()->format('ymdHis');

        $bookingCode = $data['booking_code'] ?? 'BK' . now()->format('ymdHis');

        $dataObj = BookingVisa::create([
            'booking_detail_code'     => $bookingDetailCode,
            'booking_code'            => $bookingCode,
            'surname'                 => $data['lastname'],
            'firstname'               => $data['firstname'],
            'othernames'              => $data['othernames'] ?? null,
            'passport_expiry_date'    => $data['passport_expiry_date'],
            'passport_country'        => $data['passport_country'],
            'passport_number'         => $data['passport_number'],
            'passport_issuance_date'  => $data['passport_issuance_date'],
            'emailaddress'            => $data['email_address'],
            'birth_date'              => $data['birth_date'],
            // 'document_passport_photo' => $passportPhotoPath,
            // 'document_data_page'      => $passportDataPagePath,
            'status'                  => "NEW",
            'nationality_id'          => $data['nationality_id'],
            'gender'                  => $data['gender'],
            'group_membership_id'     => $data['group_membership_id'],
            'marital_status_id'       => $data['marital_status_id'],
            'profession_id'           => $data['profession_id'],
            'language_id'             => $data['language_id'],
            'religion_id'             => $data['religion_id'],
        ]);

        return [
            'booking_code'            => $dataObj->booking_code,
            'booking_detail_code'     => $dataObj->booking_detail_code,
            'surname'                 => $dataObj->surname,
            'firstname'               => $dataObj->firstname,
            'othernames'              => $dataObj->othernames,
            'passport_expiry_date'    => $dataObj->passport_expiry_date,
            'passport_country'        => $dataObj->passport_country,
            'passport_number'         => $dataObj->passport_number,
            'passport_issuance_date'  => $dataObj->passport_issuance_date,
            'emailaddress'            => $dataObj->emailaddress,
            'birth_date'              => $dataObj->birth_date,
            // 'document_passport_photo' => secured_path($dataObj->document_passport_photo) ?? null,
            // 'document_data_page'      => secured_path($dataObj->document_data_page) ?? null,
            'status'                  => $dataObj->status,
            'nationality_id'          => $dataObj->nationality_id,
            'gender'                  => $dataObj->gender,
            'group_membership_id'     => $dataObj->group_membership_id,
            'marital_status_id'       => $dataObj->marital_status_id,
            'profession_id'           => $dataObj->profession_id,
            'language_id'             => $dataObj->language_id,
            'religion_id'             => $dataObj->religion_id,
        ];
    }

    private function uploadFile($file, $folder): string
    {
        $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
        $storage =  $file->storeAs($folder, $filename, 'public');
        return $storage;
    }

    public function createFlightBooking(
        $bookingCode,
        array $travelerPricing,
        $payload,
        $result,
        $amadeusClientRef,
        array $traveller
    ) {
        return DB::transaction(function () use (
            $bookingCode,
            $travelerPricing,
            $payload,
            $result,
            $amadeusClientRef,
            $traveller
        ) {

            $resultDecoded = $result;
            log::info($resultDecoded);
            $airline = isset($resultDecoded[0]['validatingAirlineCodes'][0]) ?? null;

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
                'flight_session' => is_array($result) ? json_encode($result) : $result,
                'payload' =>  is_array($payload) ? json_encode($payload) : $payload,
                'amadeus_client_ref' => $amadeusClientRef,
                'firstname' => $traveller['firstname'],
                'surname' => $traveller['surname'] ?? null,
                'phone_number' => $traveller['phone_number'] ?? null,
                'passport_nationality'    => $traveller['passport_nationality'] ?? null,
                'birth_date'       => $traveller['birth_date'] ?? null,
                'passport_expiry_date'  => $traveller['passport_expiry_date'] ?? null,
                'passport_issuance_date' => $traveller['passport_issuance_date'],
                'emailaddress' => $traveller['emailaddress'] ?? null,
                'gender' => $traveller['gender'] ?? null,
                'passport_country' => $traveller['passport_country'] ?? null,
                'passport_number' => $traveller['passport_number'] ?? null,
                'dialling_code' => $traveller['dialling_code']


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
    public function updateVisaBooking(array $data)
    {
        return DB::table('bookings_visas')
            ->where('booking_detail_code', $data['booking_detail_code'])
            ->update([
                'surname' => $data['lastname'],
                'firstname' => $data['firstname'],
                'othernames' => $data['othernames'],
                'passport_expiry_date' => $data['passport_expiry_date'],
                'passport_country' => $data['passport_country'],
                'passport_number' => $data['passport_number'],
                'passport_issuance_date' => $data['passport_issuance_date'],
                'emailaddress' => $data['email_address'],
                'birth_date' => $data['birthdate'],
                'nationality_id' => $data['visa_nationalities'],
                'birth_country_id' => $data['visa_birthcountry'],
                'gender' => $data['visa_gendertypes'],
                'group_membership_id' => $data['visa_groupmemberships'],
                'marital_status_id' => $data['visa_martialstatuses'],
                'profession_id' => $data['visa_professions'],
                'language_id' => $data['visa_languages'],
                'religion_id' => $data['visa_religions'],
                'document_data_page' => $data['passport_data_page_name'],
                'document_passport_photo' => $data['passport_photo_name'],
            ]);
    }

    public function updateFlightBooking(array $data)
    {
        return DB::table('bookings_flights')
            ->where('booking_detail_code', $data['booking_detail_code'])
            ->update([
                'surname' => $data['lastname'],
                'firstname' => $data['firstname'],
                'othernames' => $data['othernames'],
                'gender' => $data['gender'],
                'emailaddress' => $data['email_address'],
                'birth_date' => $data['birthdate'],
                'dialling_code' => $data['country_dialling_code'],
                'phone_number' => $data['phonenumber'],
                'passport_nationality' => $data['passport_nationality'],
                'passport_country' => $data['passport_country'],
                'passport_number' => $data['passport_number'],
                'passport_issuance_date' => $data['passport_issuance_date'],
                'passport_expiry_date' => $data['passport_expiry_date'],
                'passport_holder' => $data['passport_holder'],
            ]);
    }

    public function updateHotelBooking(array $data)
    {
        return DB::table('bookings_hotels')
            ->where('booking_detail_code', $data['booking_detail_code'])
            ->update([
                'last_name' => $data['lastname'],
                'first_name' => $data['firstname'],
                'adults' => $data['adults'],
                'children' => $data['children'],
            ]);
    }

    public function updateHotelGuest(array $data)
    {
        return DB::table('bookings_hotels_guests')
            ->where('guest_code', $data['guest_code'])
            ->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'guest_age' => $data['guest_age'],
            ]);
    }

    public function updateTourBooking(array $data)
    {
        return DB::table('bookings_tours')
            ->where('booking_detail_code', $data['booking_detail_code'])
            ->update([
                'surname' => $data['lastname'],
                'firstname' => $data['firstname'],
                'othernames' => $data['othernames'],
                'gender' => $data['gender'],
                'emailaddress' => $data['email_address'],
                'dialling_code' => $data['country_dialling_code'],
                'phone_number' => $data['phonenumber'],
                'passport_nationality' => $data['passport_nationality'],
                'passport_country' => $data['passport_country'],
                'passport_number' => $data['passport_number'],
                'passport_issuance_date' => $data['passport_issuance_date'],
                'passport_expiry_date' => $data['passport_expiry_date'],
                'passport_holder' => $data['passport_holder'],
            ]);
    }

    public function getPayment($paymentCode)
    {
        return DB::table('payments')
            ->where('payment_code', $paymentCode)
            ->first();
    }
    public function preProcessBookingFlight($booking_code)
    {
        $bookingFlights = BookingFlights::where('booking_code', $booking_code)
            ->get()
            ->toArray();

        if (empty($bookingFlights)) {
            throw new \Exception('No flights found for this booking');
        }

        $flightSearchResult = json_decode($bookingFlights[0]['flight_session'], true);
        $payload = json_decode($bookingFlights[0]['payload'], true);

        $flightOption = $payload['flight_option'] ?? 'FSC';

        $flightBooking = [
            'data' => [
                'type' => 'flight-order',
                'flightOffers' => []
            ]
        ];

        if ($flightOption === 'FSC') {

            $flightBooking['data']['flightOffers'][] = $flightSearchResult[0];
        }

        if ($flightOption === 'OWC') {
            $flightBooking['data']['flightOffers'][] = $flightSearchResult[0];
            $flightBooking['data']['flightOffers'][] = $flightSearchResult[1];
        }

        $travellers = [];
        $count = 0;

        foreach ($bookingFlights as $flight) {

            if ($flight['status'] !== 'NEW' && !is_null($flight['status'])) {
                continue;
            }

            $this->validateTraveller($flight);

            $count++;

            $travellers[] = [
                'id' => (string) $count,
                'dateOfBirth' => $flight['birth_date'],
                'name' => [
                    'firstName' => $flight['firstname'],
                    'lastName' => $flight['surname'],
                ],
                'gender' => $this->normalizeGender($flight['gender']),
                'contact' => [
                    'emailAddress' => $flight['emailaddress'],
                    'phones' => [[
                        'deviceType' => 'MOBILE',
                        'countryCallingCode' => $flight['dialling_code'],
                        'number' => $flight['phone_number'],
                    ]]
                ],
                'documents' => [[
                    'documentType' => 'PASSPORT',
                    'holder' => true,
                    'number' => $flight['passport_number'],
                    'expiryDate' => $flight['passport_expiry_date'],
                    'issuanceCountry' => $flight['passport_country'],
                    'nationality' => $flight['passport_nationality'],
                ]]
            ];
        }

        if (empty($travellers)) {
            throw new \Exception('No valid travellers found');
        }

        $flightBooking['data']['travelers'] = $travellers;
        $flightBooking['data']['ticketingAgreement'] = [
            'option' => 'DELAY_TO_CANCEL',
            'delay' => '1D'
        ];

        $flightBooking['data']['formOfPayments'][] = [
            'other' => [
                'method' => 'CASH',
                'flightOfferIds' => [
                    (string) $flightSearchResult[0]['id']
                ]
            ]
        ];

        $clientRef = $bookingFlights[0]['amadeus_client_ref'];
        Log::info('Final Amadeus Payload', [
            'payload' => $flightBooking
        ]);
        return $this->amadeusService->createFlightOrder(
            $flightBooking,
            $clientRef,
            $booking_code,
            $bookingFlights
        );
    }

    private function validateTraveller($flight)
    {
        $required = [
            'firstname',
            'surname',
            'gender',
            'birth_date',
            'passport_number',
            'passport_expiry_date',
            'passport_country',
            'passport_nationality',
            'phone_number'
        ];

        foreach ($required as $field) {
            if (empty($flight[$field])) {
                throw new \Exception("Missing traveller field: $field");
            }
        }
    }

    private function normalizeGender($gender)
    {
        $gender = strtolower($gender);

        return match ($gender) {
            'm', 'male' => 'MALE',
            'f', 'female' => 'FEMALE',
            default => throw new \Exception('Invalid gender value')
        };
    }

    public function processFlightBooking(string $sessionCode, $traveller, string $bookingCode)
    {
        $flightSession = $this->getFlightSession($sessionCode);

        if (!$flightSession) {
            throw new \Exception('Invalid flight session.');
        }
        $flightId = 1;
        $payload = $flightSession['payload'];
        $response = $flightSession['response'];

        $markup = $this->getMarkup('FLIGHT');

        if (!isset($payload['flight_option'])) {
            $payload['flight_option'] = 'FSC';
        }

        Log::info('Flight option is ' . $payload['flight_option']);

        $amadeusClientRef = $flightSession['amadeus_client_ref'];

        if ($payload['flight_option'] === 'FSC') {
            $priceOffer = $this->getAmadeusPriceOfferFSC($sessionCode, $flightId);
        } else {
            throw new \Exception('OWC option not implemented.');
        }

        $result = $priceOffer;

        if (isset($result['errors'])) {
            $errorDetail = $result['errors']['detail'] ?? 'Unknown error';
            Log::error('Unable to fetch pricing: ' . $errorDetail);
            throw new \Exception('Unable to fetch pricing - ' . $errorDetail);
        }

        $flightOffers = $result['data']['flightOffers'];

        // $userCode = auth()->user()->usercode ?? "temp" . now()->format('ymdHis');

        // $bookingCode = $this->createBooking($userCode, 'FLIGHT');

        $travelerPricings = $flightOffers[0]['travelerPricings'];
        if (!empty($traveller) && array_keys($traveller) !== range(0, count($traveller) - 1)) {
            $traveller = [$traveller];
        }
        foreach ($travelerPricings as $index => $travelerPricing) {
            $travellerData = $traveller[$index] ?? [];
            $this->createFlightBooking(
                $bookingCode,
                $travelerPricing,
                $flightSession['payload'],
                $flightOffers,
                $amadeusClientRef,
                $travellerData
            );
        }

        return [
            'booking_code' => $bookingCode,
            'message' => 'Flight booking created successfully'
        ];
    }
    public function generateBookingCode()
    {

        $userCode = auth()->user()->usercode ?? "temp" . now()->format('ymdHis');
        $bookingCode = $this->createBooking($userCode, 'FLIGHT');
        if ($bookingCode === 0) {
            Log::error('Unable to create booking');
            throw new \Exception('unable to create booking');
        }
        return [
            'bookCode' => $bookingCode['booking_code']
        ];
    }
}
