<?php

use App\Http\Controllers\AirportController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\HotelController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('flights')->group(function () {
    Route::post('/search', [FlightController::class, 'search']);
    Route::get('/session/{session_code}', [FlightController::class, 'getFlightSession']);
    Route::get('/airport-city/{code}', [FlightController::class, 'getCityFromAirportCode']);
    Route::get('/airline/{iataCode}', [FlightController::class, 'getAirline']);
    Route::post('/search-result', [FlightController::class, 'searchFlights']);
});
Route::prefix('countries')->group(function () {
    Route::get('/dialing-codes', [CountryController::class, 'dialingCodes']);
    Route::get('/iso2', [CountryController::class, 'iso2']);
    Route::get('/iso3', [CountryController::class, 'iso3']);
    Route::get('/country', [CountryController::class, 'country']);
    Route::get('/country-iso', [CountryController::class, 'countryIso']);
    Route::get('/all-countries', [CountryController::class, 'allCountries']);
});
Route::prefix('airports')->group(function () {
    Route::get('/all-airports', [AirportController::class, 'airports']);
    Route::get('/single/{code}', [AirportController::class, 'airport']);
    Route::post('/services', [AirportController::class, 'airportServices']);
    Route::post('/country', [AirportController::class, 'airportCountry']);
});
Route::prefix('hotels')->group(function () {
    Route::get('/countries', [HotelController::class, 'countries']);
    Route::get('/cities/{countryCode}', [HotelController::class, 'cities']);
    Route::get('/{hotelCode}', [HotelController::class, 'show']);
    Route::get('/{hotelCode}/amenities', [HotelController::class, 'amenities']);
    Route::post('/search', [HotelController::class, 'search']);
});
