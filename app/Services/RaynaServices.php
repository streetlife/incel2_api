<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class RaynaServices
{
    protected $key;
    protected $url;
    protected $url_tour;
    protected $url_booking;

    public function __construct()
    {
        $this->key = config('rayna.key');
        $this->url = config('rayna.url');
        $this->url_tour = config('rayna.url_tour');
        $this->url_booking = config('rayna.url_booking');
        //  dd([$this->key, $this->url, $this->url_tour, $this->url_booking]);
    }

    public function post(string $endpoint, array $payload): array
    {
        try {

            $response =Http::withHeaders([
                'Authorization' => 'Bearer' . $this->key,
                'Content-Type'  => 'application/json',
            ])
                ->timeout(30)
                ->post($this->url_tour . $endpoint, $payload);

            if ($response->failed()) {

                Log::error('Rayna Error', [
                    'endpoint' => $endpoint,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => $response->json()['message'] ?? 'Rayna API request failed'
                ];
            }

            return [
                'success' => true,
                'data' => $response->json()
            ];
        } catch (\Exception $e) {

            Log::error('Rayna Exception', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
