<?php

namespace App\Services;

use App\Mail\FlightBookingMail;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AmadeusServices
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $currency;

    public function __construct()
    {
        $this->baseUrl     = config('amadeus.base_url');
        $this->clientId    = config('amadeus.client_id');
        $this->clientSecret = config('amadeus.client_secret');
        $this->currency    = config('amadeus.currency');
    }


    public function generateClientRef(): string
    {
        return 'INCEL-' . now()->format('ymdHis') . random_int(1000, 9999);
    }

    public function getToken(): string
    {
        return Cache::remember('amadeus_token', now()->addSeconds(1700), function () {

            $response = Http::asForm()->post(
                $this->baseUrl . 'v1/security/oauth2/token',
                [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]
            )->throw();

            return $response->json()['access_token'];
        });
    }


    public function getTokenInfo(string $token): array
    {
        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get($this->baseUrl . 'v1/security/oauth2/token/' . $token)
                ->throw();

            return $response->json();
        } catch (RequestException $e) {

            Log::error('Amadeus Token Info Error', [
                'message' => $e->getMessage(),
                'token'   => $token,
            ]);

            throw new \Exception('Failed to retrieve token information');
        }
    }


    public function searchFlightOffers(
        $origin,
        $destination,
        $adults,
        $children,
        $infants,
        $tripMode,
        $dateFrom,
        $dateTo,
        $flightClass,
        $flightConnection,
        $flightOption = 'FSC',
        bool $flexibleDates = false
    ) {

        $clientRef = $this->generateClientRef();
        $token     = $this->getToken();

        $payload = [
            'currencyCode' => $this->currency,
            'originDestinations' => [
                [
                    'id' => '1',
                    'originLocationCode' => $origin,
                    'destinationLocationCode' => $destination,
                    'departureDateTimeRange' => [
                        'date' => $dateFrom,
                        ...($flexibleDates ? ['dateWindow' => 'I3D'] : [])
                    ],
                ],
            ],
            'travelers' => [],
            'sources' => ['GDS'],
            'searchCriteria' => [
                'additionalInformation' => [
                    'chargeableCheckedBags' => true,
                    'brandedFares' => false,
                ],
                'pricingOptions' => [
                    'fareType' => ['PUBLISHED', 'NEGOTIATED'],
                    'includedCheckedBagsOnly' => false,
                ],
            ],
        ];


        if ($tripMode === 'roundtrip' && $dateTo) {
            $payload['originDestinations'][] = [
                'id' => '2',
                'originLocationCode' => $destination,
                'destinationLocationCode' => $origin,
                'departureDateTimeRange' => [
                    'date' => $dateTo,
                    ...($flexibleDates ? ['dateWindow' => 'I3D'] : [])
                ],
            ];
        }

        $travelerId = 1;
        $adults = (int) $adults;

        if ($adults < 1 || $adults > 9) {
            throw new \Exception('Invalid number of adults');
        }

        for ($i = 0; $i < $adults; $i++) {
            $payload['travelers'][] = [
                'id' => (string) $travelerId++,
                'travelerType' => 'ADULT',
            ];
        }


        for ($i = 0; $i < $children; $i++) {
            $payload['travelers'][] = [
                'id' => (string) $travelerId++,
                'travelerType' => 'CHILD',
            ];
        }


        $adultIds = collect($payload['travelers'])
            ->where('travelerType', 'ADULT')
            ->pluck('id')
            ->values();

        $adultIndex = 0;

        for ($i = 0; $i < $infants; $i++) {
            $payload['travelers'][] = [
                'id' => (string) $travelerId++,
                'travelerType' => 'HELD_INFANT',
                'associatedAdultId' => $adultIds[$adultIndex] ?? '1',
            ];

            $adultIndex = ($adultIndex + 1) % max(count($adultIds), 1);
        }


        $cabinMap = [
            'economy'         => 'ECONOMY',
            'premium_economy' => 'PREMIUM_ECONOMY',
            'business'        => 'BUSINESS',
            'first_class'     => 'FIRST_CLASS',
        ];

        if (isset($cabinMap[$flightClass])) {
            $payload['searchCriteria']['pricingOptions']['flightFilters']['cabinRestrictions'] = [
                'cabin' => $cabinMap[$flightClass],
            ];
        }


        if (in_array($flightConnection, ['0', '1', '2'])) {
            $payload['searchCriteria']['flightFilters']['connectionRestriction'] = [
                'maxNumberOfConnections' => $flightConnection,
            ];
        }


        if ($flightOption === 'OWC') {
            $payload['searchCriteria']['addOneWayOffers'] = true;
        }

        Log::info('Amadeus Request', $payload);

        $response = Http::withToken($token)
            ->withHeaders([
                'ama-client-ref' => $clientRef,
            ])
            ->post($this->baseUrl . 'v2/shopping/flight-offers', $payload)
            ->throw();

        Log::info('Amadeus Response', [
            'status' => $response->status(),
        ]);

        return $response->json();
    }

    public function searchMultiCityFlightOffers(
        $travelFrom,
        $travelTo,
        $travelDate,
        $adults,
        $children,
        $infants,
        $flightClass,
        $flightConnection
    ) {

        $clientRef = $this->generateClientRef();
        $token     = $this->getToken();

        $payload = [
            'currencyCode' => $this->currency,
            'originDestinations' => [],
            'travelers' => [],
            'sources' => ['GDS'],
            'searchCriteria' => [
                'pricingOptions' => [
                    'includedCheckedBagsOnly' => false,
                ],
                'maxFlightOffers' => 250,
            ],
        ];


        foreach ($travelFrom as $key => $from) {

            $payload['originDestinations'][] = [
                'id' => (string) ($key + 1),
                'originLocationCode' => $from,
                'destinationLocationCode' => $travelTo[$key] ?? null,
                'departureDateTimeRange' => [
                    'date' => $travelDate[$key] ?? null,
                ],
            ];
        }


        $travelerId = 1;


        for ($i = 0; $i < $adults; $i++) {
            $payload['travelers'][] = [
                'id' => (string) $travelerId++,
                'travelerType' => 'ADULT',
            ];
        }


        for ($i = 0; $i < $children; $i++) {
            $payload['travelers'][] = [
                'id' => (string) $travelerId++,
                'travelerType' => 'CHILD',
            ];
        }


        $adultIds = collect($payload['travelers'])
            ->where('travelerType', 'ADULT')
            ->pluck('id')
            ->values();

        $adultIndex = 0;

        for ($i = 0; $i < $infants; $i++) {

            $payload['travelers'][] = [
                'id' => (string) $travelerId++,
                'travelerType' => 'HELD_INFANT',
                'associatedAdultId' => $adultIds[$adultIndex] ?? '1',
            ];

            $adultIndex = ($adultIndex + 1) % max(count($adultIds), 1);
        }


        $cabinMap = [
            'economy' => 'ECONOMY',
            'premium_economy' => 'PREMIUM_ECONOMY',
            'business' => 'BUSINESS',
            'first_class' => 'FIRST_CLASS',
        ];

        if (isset($cabinMap[$flightClass])) {
            $payload['searchCriteria']['pricingOptions']['flightFilters']['cabinRestrictions'] = [
                'cabin' => $cabinMap[$flightClass],
            ];
        }

        if (in_array($flightConnection, ['0', '1', '2'])) {
            $payload['searchCriteria']['flightFilters']['connectionRestriction'] = [
                'maxNumberOfConnections' => $flightConnection,
            ];
        }

        Log::info('Amadeus Multi-City Request', $payload);

        $response = Http::withToken($token)
            ->withHeaders([
                'ama-client-ref' => $clientRef,
            ])
            ->post($this->baseUrl . 'v2/shopping/flight-offers', $payload)
            ->throw();

        Log::info('Amadeus Multi-City Response', [
            'status' => $response->status(),
        ]);

        return $response->json();
    }
    // public function createFlightOrder($payload, $clientRef, $bookingCode, $bookingFlights)
    // {
    //     $token = $this->getToken();

    //     $response = Http::withHeaders([
    //         'Content-Type' => 'application/json',
    //         'ama-client-ref' => $clientRef,
    //         'Authorization' => 'Bearer ' . $token,
    //     ])->post($this->baseUrl . 'v1/booking/flight-orders', $payload);

    //     Log::info('Amadeus Request', $payload);
    //     Log::info('Amadeus Response', $response->json());

    //     if ($response->failed()) {
    //         Log::error('Flight booking failed', $response->json());
    //         return null;
    //     }

    //     $result = $response->json();

    //     $flightBookingId = $result['data']['id'] ?? null;
    //     $flightPNR = $result['data']['associatedRecords'][0]['reference'] ?? null;
    //     $ticketNumber = $result['data']['travelers'][0]['documents'][0]['number'] ?? null;

    //     if (!$flightBookingId || !$flightPNR) {
    //         return null;
    //     }

    //     Log::info('ticketNumber', [
    //         'ticketNumber' => $ticketNumber
    //     ]);
    //     foreach ($bookingFlights as $flight) {
    //         DB::table('bookings_flights')
    //             ->where('booking_detail_code', $flight['booking_detail_code'])
    //             ->update([
    //                 'flight_booking_id' => $flightBookingId,
    //                 'flightPNR' => $flightPNR,
    //             ]);
    //     }

    //     DB::table('bookings')
    //         ->where('booking_code', $bookingCode)
    //         ->update(['booking_status' => 'BOOKED']);
    //     // Send email here
    //     foreach ($bookingFlights as $flight) {
    //         if (!empty($flight['emailaddress'])) {
    //             $emailData = [
    //                 'name' => $flight['firstname'] . ' ' . $flight['surname'],
    //                 'pnr' => $flightPNR,
    //                 'ticket_number' => $ticketNumber,
    //                 'flight_number' => $flight['flight_number'] ?? 'N/A',
    //             ];

    //             // Mail::to($flight['emailaddress'])->send(new FlightBookingMail($emailData));
    //         }
    //     }
    //     return $flightPNR;
    // }
    public function createFlightOrder($payload, $clientRef, $bookingCode, $bookingFlights)
    {
        $token = $this->getToken();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'ama-client-ref' => $clientRef,
            'Authorization' => 'Bearer ' . $token,
        ])->post($this->baseUrl . 'v1/booking/flight-orders', $payload);

        Log::info('Amadeus Request', $payload);
        Log::info('Amadeus Response', $response->json());

        if ($response->failed()) {

            $errorResponse = $response->json();

            Log::error('Flight booking failed', $errorResponse);

            $errorMessage = $errorResponse['errors'][0]['detail']
                ?? $errorResponse['errors'][0]['title']
                ?? 'Flight booking failed';

            return [
                'status' => false,
                'message' => $errorMessage,
                'error' => $errorResponse
            ];
        }

        $result = $response->json();

        $flightBookingId = $result['data']['id'] ?? null;
        $flightPNR = $result['data']['associatedRecords'][0]['reference'] ?? null;
        $ticketNumber = $result['data']['travelers'][0]['documents'][0]['number'] ?? null;

        if (!$flightBookingId || !$flightPNR) {

            return [
                'status' => false,
                'message' => 'Invalid booking response from Amadeus',
                'error' => $result
            ];
        }

        Log::info('ticketNumber', [
            'ticketNumber' => $ticketNumber
        ]);

        foreach ($bookingFlights as $flight) {
            DB::table('bookings_flights')
                ->where('booking_detail_code', $flight['booking_detail_code'])
                ->update([
                    'flight_booking_id' => $flightBookingId,
                    'flightPNR' => $flightPNR,
                ]);
        }

        DB::table('bookings')
            ->where('booking_code', $bookingCode)
            ->update([
                'booking_status' => 'BOOKED'
            ]);

        // Send email
        foreach ($bookingFlights as $flight) {
            if (!empty($flight['emailaddress'])) {

                $emailData = [
                    'name' => $flight['firstname'] . ' ' . $flight['surname'],
                    'pnr' => $flightPNR,
                    'ticket_number' => $ticketNumber,
                    'flight_number' => $flight['flight_number'] ?? 'N/A',
                ];

                // Mail::to($flight['emailaddress'])->send(new FlightBookingMail($emailData));
            }
        }

        return [
            'status' => true,
            'message' => 'Flight booked successfully',
            'data' => [
                'pnr' => $flightPNR,
                'ticket_number' => $ticketNumber
            ]
        ];
    }
    public function priceOfferOWC(array $flightOffers, string $clientRef): array
    {
        $token = $this->getToken();

        $payload = [
            'data' => [
                'type' => 'flight-offers-pricing',
                'flightOffers' => $flightOffers
            ]
        ];

        $url = $this->baseUrl . 'v1/shopping/flight-offers/pricing';

        if (isset($flightOffers[0]['price']['additionalServices'])) {
            $url .= '?include=bags';
        }

        Log::info('Amadeus Pricing Request', $payload);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'ama-client-ref' => $clientRef,
            'Authorization' => 'Bearer ' . $token,
        ])->post($url, $payload);

        Log::info('Amadeus Pricing Response', [
            'status' => $response->status(),
            'body' => $response->json()
        ]);

        if (!$response->successful()) {
            throw new \Exception('Amadeus pricing request failed.');
        }

        return $response->json();
    }
    public function priceOfferFSC(
        array $flightOffer,
        string $clientRef,
        int $flightId
    ): array {

        $token = $this->getToken();

        $payload = [
            'data' => [
                'type' => 'flight-offers-pricing',
                'flightOffers' => [
                    $flightOffer
                ],
                'formOfPayments' => [
                    [
                        'other' => [
                            'method' => 'CASH',
                            'flightOfferIds' => [
                                (string) $flightId
                            ]
                        ]
                    ]
                ],
                'additionalInformation' => [
                    'fareRules' => true
                ]
            ]
        ];

        $url = $this->baseUrl . 'v1/shopping/flight-offers/pricing';

        if (isset($flightOffer['price']['additionalServices'])) {
            $url .= '?include=bags';
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'ama-client-ref' => $clientRef,
            'Authorization' => 'Bearer ' . $token,
        ])->post($url, $payload);

        if (!$response->successful()) {
            throw new \Exception('Amadeus FSC pricing failed.');
        }

        return $response->json();
    }
}
