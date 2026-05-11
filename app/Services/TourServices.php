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
      
        log::info("respon ryna",[$response['data']]);
        
        if (
            empty($response) ||
            !isset($response['response']) ||
            !is_array($response['response'])
        ) {
            return [
                'status' => false,
                'message' => 'Invalid response from Rayna API',
                'data' => $response
            ];
        }

        DB::beginTransaction();

        try {

            DB::table('sessions_tours')->insert([
                'session_code'    => $search_session_code,
                'country_id'      => $countryId,
                'city_id'         => $cityId,
                'travel_date'     => $date,
                'currency'        => $response['currency'] ?? null,
                'currency_symbol' => $response['currencysymbol'] ?? null,
                'result_count'    => $response['count'] ?? count($response['response']),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            $tourResults = [];

            foreach ($response['response'] as $tour) {

                $tourResults[] = [
                    'session_code' => $search_session_code,
                    'tour_id' => $tour['tourId'] ?? null,
                    'contract_id' => $tour['contractId'] ?? null,
                    'amount' => $tour['amount'] ?? 0,
                    'discount' => $tour['discount'] ?? 0,
                    'reward_points' => $tour['rewardPoints'] ?? 0,
                    'sort_order' => $tour['sortOrder'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($tourResults)) {
                DB::table('sessions_tours_results')->insert($tourResults);
            }

            DB::commit();

            $response['session_code'] = $search_session_code;

            return ['status'=>true,'data'=>$response['data'],'message'=>'Successful'];
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Tour Search Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => false,
                'message' => $e->getMessage()
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

            if (!$response['success']) {
                return [
                    'status'  => false,
                    'message' => $response['message'] ?? 'Booking failed',
                ];
            }

            $result = $response['data'];

            if (($result['statuscode'] ?? null) === 200) {
                $this->handleSuccess($bookingCode, $result, json_encode($response));

                return [
                    'status'  => true,
                    'message' => 'Booking processed successfully',
                    'data'    => [
                        'booking_id'      => $result['result']['details']['bookingId'],
                        'confirmation_no' => $result['result']['details']['confirmationNo'],
                        'reference_no'    => $result['result']['referenceNo'],
                    ],
                ];
            }

            return ['status' => false, 'message' => 'Booking failed'];
        } catch (\Throwable $th) {
            Log::error('TourBookingService Exception', ['error' => $th->getMessage()]);

            return ['status' => false, 'message' => 'Something went wrong.'];
        }
    }
    private function buildPayload(array $bookingTours): array
    {
        $uniqueId = date('ym') . random_int(10, 99);
        $first    = $bookingTours[0];

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
        DB::table('bookings')
            ->where('booking_code', $bookingCode)
            ->update([
                'api_response'    => $rawResponse,
                'booking_id'      => $result['result']['details']['bookingId'],
                'confirmation_no' => $result['result']['details']['confirmationNo'],
                'reference_no'    => $result['result']['referenceNo'],
                'status'          => 'PROCESSED',
                'updated_at'      => now(),
            ]);
    }
}
