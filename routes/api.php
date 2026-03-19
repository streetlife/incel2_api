<?php

use App\Http\Controllers\AboutusController;
use App\Http\Controllers\AirportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\FlutterwaveController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PaystackController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisaController;
use App\Http\Controllers\TravelController;
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

Route::middleware('auth:sanctum')->group(function () {

    Route::post("/changePassword", [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::prefix('users')->group(function () {
        Route::get('/users-by-code/{userCode}', [UserController::class, 'getUserByUserCode']);
        Route::get('/auth-users-by-email', [UserController::class, 'getAuthenticatedUserByEmail']);
        Route::get('/user-profile',[AuthController::class,'getProfile']);
        Route::patch('/user-profile', [AuthController::class, 'updateProfile']);
    });
});
Route::get('/reviews', [UserController::class, 'index']);
Route::post('/reviews', [UserController::class, 'store']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/check-user', [AuthController::class, 'checkUser']);
Route::prefix('services')->group(function () {
    Route::post('/insurance', [ServiceRequestController::class, 'createInsuranceService']);
    Route::post('/airport-transfer', [ServiceRequestController::class, 'airportTransfer']);
    Route::post('/airport-protocol', [ServiceRequestController::class, 'airportProtocol']);
    Route::post('/hot-deal', [ServiceRequestController::class, 'hotDeals']);
    Route::post('/tour-guide', [ServiceRequestController::class, 'tourGuide']);
    Route::get('/packages', [ServiceRequestController::class, 'getTravelPackages']);
    Route::get('/packages/search/{country_code}', [ServiceRequestController::class, 'searchPackages']);
    Route::get('/hotdeals', [ServiceRequestController::class, 'getHotdeals']);
    Route::get('/hot-deal/{id}', [ServiceRequestController::class, 'getHotdealsById']);
    Route::get('/travel-package/{id}', [ServiceRequestController::class, 'getTravelPackageById']);
    Route::post('/add-stats', [ServiceRequestController::class, 'addStats']);
    Route::get('/stats', [ServiceRequestController::class, 'getStats']);
    Route::get('/partner', [ServiceRequestController::class, 'getAllPartner']);
    Route::post('/hero-section', [ServiceRequestController::class, 'createHeroSection']);
    Route::get('/hero-section', [ServiceRequestController::class, 'getHeroSection']);
    Route::post('/testmonial', [ServiceRequestController::class, 'createVideoTestmonial']);
    Route::post('/package/{id}',[ServiceRequestController::class, 'update']);
    Route::post('/contact', [ServiceRequestController::class, 'store']);
});

Route::prefix('flights')->group(function () {
    Route::post('/search', [FlightController::class, 'search']);
    Route::get('/session/{session_code}', [FlightController::class, 'getFlightSession']);
    Route::get('/airport-city/{code}', [FlightController::class, 'getCityFromAirportCode']);
    Route::get('/airline/{iataCode}', [FlightController::class, 'getAirline']);
    Route::post('/search-result', [FlightController::class, 'searchFlights']);
    Route::post('/book-flight', [FlightController::class, 'bookFlight']);
    Route::get('/airports/search', [AirportController::class, 'search']);
    Route::get('/preProcessBookingFlight/{booking_code}', [BookingController::class, 'preProcessBookingFlight']);
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
Route::prefix('tours')->group(function () {
    Route::post('/search', [TourController::class, 'search']);
});
Route::prefix('bookings')->group(function () {
    Route::post('/', [BookingController::class, 'create']);
    Route::get('/{bookingCode}', [BookingController::class, 'show']);
    Route::post('/tour', [BookingController::class, 'addTour']);

    Route::post('/flight', [BookingController::class, 'addFlight']);
    Route::post('/hotel', [BookingController::class, 'addHotel']);
    Route::post('/hotel/guest', [BookingController::class, 'addHotelGuest']);
    Route::post('/visa/update', [BookingController::class, 'updateVisa']);
    Route::post('/flight/update', [BookingController::class, 'updateFlight']);
    Route::get('/payment/{paymentCode}', [BookingController::class, 'getPayment']);
    Route::post('/generate-booking-code', [BookingController::class, 'generateBookingCode']);
});
Route::prefix('currencies')->group(function () {
    Route::get('/', [CurrencyController::class, 'index']);
    Route::post('/currency/convert', [CurrencyController::class, 'convert']);
});
Route::prefix('paystack')->group(function () {
    Route::post('/payment/initialize', [PaystackController::class, 'initialize']);
});
Route::prefix('flutterwave')->group(function () {
    Route::post('/payment/initialize', [FlutterwaveController::class, 'initialize']);
});
Route::prefix('visas')->group(function () {

    Route::get('/metadata', [VisaController::class, 'getMetadata']);
    Route::get('/session/{code}', [VisaController::class, 'showSession']);
    Route::get('/{id}', [VisaController::class, 'visaById']);
    Route::post('/search', [VisaController::class, 'search']);
    Route::get('/session/{session_code}', [VisaController::class, 'getSession']);
    Route::post('/create-visa', [BookingController::class, 'addVisa']);
    Route::post('/payment', [VisaController::class, 'payment']);

    Route::prefix('travellers')->group(function () {
        Route::get('/', [TravelController::class, 'index']);
        Route::post('/', [TravelController::class, 'store']);
        Route::get('/stats/{usercode}', [TravelController::class, 'stats']);
        Route::get('/{access_code}', [TravelController::class, 'show']);
        Route::patch('/{access_code}', [TravelController::class, 'update']);
        Route::get('/generate/booking-code', [TravelController::class, 'generateBookingCode']);
    });
});

Route::post('/subscribe', [NewsletterController::class, 'sendNewsletter']);
Route::prefix('about-us')->group(function () {
    Route::post('/create', [AboutusController::class, 'create']);
    Route::get('/', [AboutusController::class, 'getAll']);
});
