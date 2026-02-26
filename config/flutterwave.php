<?php

return [
 'public_key'=>env('FLUTTERWAVE_PUBLIC_KEY'),
 'secret_key'=> env('FLUTTERWAVE_SECRET_KEY'),
 'test_key' => env('FLUTTERWAVE_TEST_KEY'),
 'payment_url'=>env('FLUTTERWAVE_URL_PAYMENTS'),
 'verify_url' => env('FLUTTERWAVE_URL_VERIFY')
];