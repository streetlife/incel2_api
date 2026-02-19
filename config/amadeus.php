<?php 

return [
    'base_url' => env('AMADEUS_URL'),
    'client_id' => env('AMADEUS_CLIENT_ID'),
    'client_secret' => env('AMADEUS_CLIENT_SECRET'),
    'company_code' => env('AMADEUS_COMPANY_CODE'),
    'currency' => env('FLIGHT_CURRENCY', 'USD'),
];
