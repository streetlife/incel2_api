<?php

namespace App\Services;

use App\Models\TourCites;
use App\Models\TourCountry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TourServices
{

    public function __construct(public RaynaServices $raynaService) {}

    public function search(int $countryId, int $cityId, string $date): array
    {
        $payload = [
            'countryId'  => $countryId,
            'cityId'     => $cityId,
            'travelDate' => $date,
        ];

        $search_session_code = uniqid('tour');

        $response = $this->raynaService->post('tourlist/', $payload);

        Log::info("Rayna raw response", [$response]);

        if (
            empty($response) ||
            ($response['data']['statuscode'] ?? null) !== 200 ||
            !isset($response['data']['result']) ||
            !is_array($response['data']['result'])
        ) {
            return [
                'status'  => false,
                'message' => $response['data']['errormessage'] ?? 'Invalid response from Rayna API',
                'data'    => null,
            ];
        }

        $responseData = $response['data'];

        DB::beginTransaction();

        try {
            DB::table('sessions_tours')->insert([
                'session_code'    => $search_session_code,
                'country_id'      => $countryId,
                'city_id'         => $cityId,
                'travel_date'     => $date,
                'currency'        => $responseData['currency']       ?? null,
                'currency_symbol' => $responseData['currencysymbol'] ?? null,
                'result_count'    => $responseData['count']          ?? count($responseData['result']),
                // 'created_at'      => now(),
                // 'updated_at'      => now(),
            ]);

            $tourResults = [];

            foreach ($responseData['result'] as $tour) {
                $tourResults[] = [
                    'session_code'  => $search_session_code,
                    'tour_id'       => $tour['tourId']      ?? null,
                    'contract_id'   => $tour['contractId']  ?? null,
                    'amount'        => $tour['amount']       ?? 0,
                    'discount'      => $tour['discount']     ?? 0,
                    'reward_points' => $tour['rewardPoints'] ?? 0,
                    'sort_order'    => $tour['sortOrder']    ?? 0,
                    // 'created_at'    => now(),
                    // 'updated_at'    => now(),
                ];
            }

            if (!empty($tourResults)) {
                DB::table('sessions_tours_results')->insert($tourResults);
            }

            DB::commit();

            $data = [
                'country_id'  => $countryId,
                'city_id'     => $cityId,
                'tour_id'     => $responseData['result'][0]['tourId']     ?? null,
                'contract_id' => $responseData['result'][0]['contractId'] ?? null,
                'travel_date' => $date,
            ];



            $result = $this->getTourStaticData($data);

            return [
                'status'       => true,
                'message'      => 'Tours fetched successfully',
                'session_code' => $search_session_code,
                'data'         => $result,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Tour Search Error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return [
                'status'  => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    public function getTourCities(int $countryId): array
    {
        try {
            $repsonse = TourCites::where('country_id', $countryId)
                ->select(['city_name', 'city_id'])
                ->get()
                ->toArray();
            return ['status' => true, 'message' => "Data retrieved", 'data' => $repsonse];
        } catch (\Throwable $th) {
            Log::error('getTourCities Error', [
                'countryId' => $countryId,
                'message'   => $th->getMessage(),
            ]);
            return [
                'status' => false,
                'message' => "Something went wrong",
                'data' => []
            ];
        }
    }
    function getTourCountries()
    {
        try {
            $query = TourCountry::get(['country_id as id', 'country_name as name']);

            return [
                'status' => true,
                'message' => 'Retrieved',
                'data' => $query
            ];
        } catch (\Throwable $th) {
            Log::info("info", [$th->getMessage()]);
            return [
                'status' => true,
                'message' => 'Something went wrong',
            ];
        }
    }

    public function processBooking(string $bookingCode, array $bookingTours): array
    {
        try {
            $payload  = $this->buildPayload($bookingTours);
            $response = $this->raynaService->booking('Bookings', $payload);

            if (empty($response)) {
                return [
                    'status'  => false,
                    'message' => 'Booking failed, no response from API',
                ];
            }

            $result = $response['data'];

            if (($result['statuscode'] ?? null) === 200) {

                if (empty($result['result'])) {
                    return [
                        'status'  => false,
                        'message' => $result['error']['description'] ?? 'Booking failed',
                    ];
                }

                $details = $result['result']['details'][0] ?? null; // ✅ first item in array

                if (empty($details) || $details['status'] !== 'Success') {
                    return [
                        'status'  => false,
                        'message' => 'Booking was not successful',
                    ];
                }

                $this->handleSuccess($bookingCode, $result, json_encode($response));

                return [
                    'status'  => true,
                    'message' => 'Booking processed successfully',
                    'data'    => [
                        'booking_id'      => $details['bookingId'],
                        // 'confirmation_no' => $details['confirmationNo'],
                        'reference_no'    => $result['result']['referenceNo'],
                    ],
                ];
            }
            return [
                'status'  => false,
                'message' => $result['error']['description'] ?? 'Booking failed',
            ];
        } catch (\Throwable $th) {
            Log::error('TourBookingService Exception', ['error' => $th->getMessage()]);

            return ['status' => false, 'message' => 'Something went wrong.'];
        }
    }
    private function buildPayload(array $bookingTours): array
    {
        $uniqueId = date('ym') . random_int(10, 99);
        $first    = $bookingTours[0];
        // Log::info('Building Payload - Traveller Types', [
        //     'travellers' => array_map(fn($t) => [
        //         'type'   => $t['traveller_type'],
        //         'amount' => $t['amount'],
        //     ], $bookingTours)
        // ]);

        $tourDetails = [
            'serviceUniqueId' => $uniqueId,
            'tourId'          => $first['tour_id'],
            'optionId'        => $first['tour_option_id'],
            'adult'           => 0,
            'child'           => 0,
            'infant'          => 0,
            'tourDate'        => $first['travel_date'],
            'timeSlotId'      => (int) $first['time_slot_id'],
            'startTime'       => '11:00:00',
            'transferId'      => (int) $first['transfer_id'],
            'pickup'          => 'Test Location',
            'adultRate'       => 0,
            'childRate'       => 0,
            'serviceTotal'    => 0,
        ];

        $passengers = [];

        foreach ($bookingTours as $index => $tour) {
            $amount = (float) $tour['amount'];

            match ($tour['traveller_type']) {
                'ADULT'  => $tourDetails['adult']++,
                'CHILD',
                'INFANT' => $tourDetails['child']++,
                default  => null,
            };

            if ($tour['traveller_type'] === 'ADULT') {
                $tourDetails['adultRate'] = $amount;
            } else {
                $tourDetails['childRate'] = $amount;
            }

            $tourDetails['serviceTotal'] += $amount;

            $passengers[] = [
                'serviceType'       => 'Tour',
                'prefix'            => 'Mr',
                'firstName'         => $tour['firstname'],
                'lastName'          => $tour['surname'],
                'email'             => $tour['emailaddress'],
                'mobile'            => $tour['phone_number'],
                'nationality'       => $tour['passport_nationality'],
                'message'           => '',
                'leadPassenger'     => $index === 0 ? 1 : 0,
                'clientReferenceNo' => $tour['booking_detail_code'],
                'paxType'           => ucfirst(strtolower($tour['traveller_type'])),
            ];
        }

        $tourDetails['serviceTotal'] = round($tourDetails['serviceTotal'], 2);

        return [
            'uniqueNo'    => $uniqueId,
            'TourDetails' => [$tourDetails],
            'passengers'  => $passengers,
        ];
    }
    private function handleSuccess(string $bookingCode, array $result, string $rawResponse): void
    {
        $details = $result['result']['details'][0];

        DB::table('bookings')
            ->where('booking_code', $bookingCode)
            ->update([
                'response'    => $rawResponse,
                // 'booking_id'      => $details['bookingId'],
                // 'confirmation_no' => $details['confirmationNo'],
                // 'reference_no'    => $result['result']['referenceNo'],
                'booking_status'    => 'PROCESSED',
                'date_created'      => now(),
            ]);
    }
    public function getTourStaticData(array $data): array
    {
        try {
            $result = $this->raynaService->getTourStaticData(
                tourCountryId: $data['country_id'],
                tourCityId: $data['city_id'],
                tourId: $data['tour_id'],
                tourContractId: $data['contract_id'],
                travelDate: $data['travel_date'],
            );

            if (empty($result)) {
                return [
                    'status'  => false,
                    'message' => $result['message'] ?? 'Failed to fetch tour static data',
                    'data'    => null,
                ];
            }

            return [
                'status'  => true,
                'message' => 'Tour static data fetched successfully',
                'data'    => $result['data'],
            ];
        } catch (\Throwable $th) {
            Log::error('getTourStaticData Error', ['message' => $th->getMessage()]);

            return [
                'status'  => false,
                'message' => 'Something went wrong.',
                'data'    => null,
            ];
        }
    }
    public function getTourPricing(array $data)
    {
        try {
            $result = $this->raynaService->getTourPricing(
                tourId: $data['tour_id'],
                contractId: $data['contract_id'],
                travelDate: $data['travel_date'],
            );

            if (empty($result) || !$result['status']) {
                return [
                    'status'  => false,
                    'message' => $result['message'] ?? 'Failed to fetch tour pricing data',
                    'data'    => null,
                ];
            }

            return [
                'status'  => true,
                'message' => 'Tour pricing fetched successfully',
                'data'    => $result['data'],
            ];
        } catch (\Throwable $th) {
            Log::error('getTourPricing Error', ['message' => $th->getMessage()]);

            return [
                'status'  => false,
                'message' => 'Something went wrong.',
                'data'    => null,
            ];
        }
    }
}
