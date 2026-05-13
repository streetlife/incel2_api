<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RaynaServices
{
    protected string $key;
    protected string $url;
    protected string $url_tour;
    protected string $url_booking;

    public function __construct()
    {
        $this->key = config('rayna.key');
        $this->url = config('rayna.url');
        $this->url_tour = config('rayna.url_tour');
        $this->url_booking = config('rayna.url_booking');
        // Log::info('Rayna Config', [
        //     'key'      => $this->key,       // is it null or empty?
        //     'url_tour' => $this->url_tour,
        // ]);
    }

    /**
     * Generic POST request to Rayna API
     */
    public function post(string $endpoint, array $payload): array
    {
        try {

            $url = rtrim($this->url_tour, '/') . '/' . ltrim($endpoint, '/');


            Log::info('Rayna Request', [
                'url' => $url,
                'payload' => $payload,
            ]);
            Log::info('Rayna Auth Header', [
                'header' => 'Bearer ' . $this->key,
            ]);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->key,
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post($url, $payload);

            if ($response->failed()) {

                Log::info('Rayna Error', [
                    'url'      => $url,
                    'endpoint' => $endpoint,
                    'payload'  => $payload,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                    'message' => $response
                ]);

                return [
                    'success' => false,
                    'status'  => $response->status(),
                    'message' => $response->json()['message'] ?? 'Rayna API request failed',
                    'raw'     => $response->body(),
                ];
            }
            log::info("response", [$response->json()]);
            return [
                // 'success' => true,
                // 'status'  => $response->status(),
                'data'    => $response->json(),
            ];
        } catch (\Exception $e) {

            Log::error('Rayna Exception', [
                'endpoint' => $endpoint,
                'message'  => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    public function booking(string $endpoint, array $payload): array
    {
        try {
            $url = rtrim($this->url_booking, '/') . '/' . ltrim($endpoint, '/');

            Log::info('Rayna Booking Request', ['url' => $url, 'payload' => $payload]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->key,
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post($url, $payload);

            if ($response->failed()) {
                Log::error('Rayna Booking Error', [
                    'url'    => $url,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return [
                    'success' => false,
                    'status'  => $response->status(),
                    'message' => $response->json()['message'] ?? 'Rayna booking request failed',
                    'raw'     => $response->body(),
                ];
            }

            Log::info('Rayna Booking Response', [$response->json()]);

            return [
                // 'success' => true,
                // 'status'  => $response->status(),
                'data'    => $response->json(),
            ];
        } catch (\Exception $e) {
            // Log::error('Rayna Booking Exception', [
            //     'endpoint' => $endpoint,
            //     'message'  => $e->getMessage(),
            // ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getTourStaticData(
        int    $tourCountryId,
        int    $tourCityId,
        int    $tourId,
        int    $tourContractId,
        string $travelDate
    ): array {
        $payload = [
            'countryId'  => $tourCountryId,
            'cityId'     => $tourCityId,
            'tourId'     => $tourId,
            'contractId' => $tourContractId,
            'travelDate' => $travelDate,
        ];

        $response = $this->post('tourStaticDataById', $payload);

        if (empty($response)) {
            return [
                'status'  => false,
                'message' => $response['message'] ?? 'Unable to fetch tour static data',
                'data'    => null,
            ];
        }

        return [
            'status'  => true,
            'message' => 'Tour static data fetched successfully',
            'data'    => $response['data'],
        ];
    }
    public function getTourPricing(int $tourId, int $contractId, string $travelDate)
    {
        $payload = [
            "tourId"     => $tourId,
            "contractId" => $contractId,
            "travelDate" => $travelDate,
            "noOfAdult"  => 1,
            "noOfChild"  => 1,
            "noOfInfant" => 1
        ];
        $response = $this->post("touroption", $payload);
        // Log::info("response tour pricing ", [$response]);
        if (empty($response)) {
            return [
                'status'  => false,
                'message' => $response['message'] ?? 'Unable to fetch tour static data',
                'data'    => null,
            ];
        }

        return  $response['data'];

    }
}
