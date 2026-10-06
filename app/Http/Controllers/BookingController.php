<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingVisaRequest;
use App\Services\BookingServices;
use FFI\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    protected $bookingService;

    public function __construct(BookingServices $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'sessionCode' => 'required|string',
            'traveller' => 'nullable|array|min:1',
            'traveller.*.firstname' => 'nullable|string',
            'traveller.*.surname' => 'nullable|string',
            'traveller.*.birth_date' => 'nullable|string',
            'traveller.*.passport_expiry_date' => 'nullable|string',
            'traveller.*.passport_nationality' => 'nullable|string',
            'traveller.*.phone_number' => 'nullable|string',
            'traveller.*.passport_issuance_date' => 'nullable|string',
            'traveller.*.emailaddress' => 'nullable|string',
            'traveller.*.gender' => 'nullable|string',
            'traveller.*.passport_number' => 'nullable|string',
            'traveller.*.passport_country' => 'nullable|string',
            'traveller.*.dialling_code' => 'nullable|integer',
            'bookingCode' => "nullable|string"
        ]);
        $booking = $this->bookingService->processFlightBooking(
            $validated['sessionCode'],
            $validated['traveller'],
            $validated['bookingCode']

        );

        audit_log(
            'booking',
            'flight_created',
            'booking.flight_created',
            'Flight booking created',
            bookingCode: $validated['bookingCode'],
            newValues: $booking,
            status: 'success',
        );

        return response()->json([
            'status' => true,
            'data'   => $booking
        ]);
    }
    public function generateBookingCode()
    {
        $booking = $this->bookingService->generateBookingCode();
        if (!$booking) {
            audit_log('booking', 'code_generation_failed', 'booking.generate_code', 'Booking code generation failed', status: 'failure');

            return response()->json([
                'status' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        audit_log(
            'booking',
            'code_generated',
            'booking.generate_code',
            'Booking code generated',
            bookingCode: $booking['bookCode'] ?? null,
            status: 'success',
        );

        return response()->json([
            'status' => true,
            'message' => 'Booking created successfully',
            'data' => $booking
        ], 200);
    }
    public function show($bookingCode)
    {
        $booking = $this->bookingService->getBooking($bookingCode);

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $booking
        ]);
    }

    public function addTour(Request $request)
    {
        Log::info($request);
        $request->validate([
            'traveller_code'      => 'required|string',
            'tour_id'             => 'required|integer',
            'travel_date'         => 'required|date',
            'currency_code'       => 'required|string',
            'amount'              => 'required|numeric',
            'amount_display'      => 'required|numeric',
            'status'              => 'required|string',
            'transfer_id'         => 'nullable|integer',
            'transfer_option'     => 'nullable|string',
            'transfer_name'       => 'nullable|string',
            'contract_id'         => 'nullable|integer',
            'tour_option_id'      => 'nullable|integer',
            'traveller_type'      => 'required|string',
            'time_slot_id'        => 'nullable|integer',
            'time_slot_name'      => 'nullable|string',
            'surname'             => 'nullable|string',
            'firstname'           => 'nullable|string',
            'othernames'          => 'nullable|string',
            'gender'              => 'nullable|string',
            'phone_number'         => 'nullable|string',
            'emailaddress'        => 'nullable|string',
            'passport_nationality' => 'nullable|string'

        ]);

        $tour = $this->bookingService->createTourBooking($request->all());

        audit_log(
            'tour',
            'booking_created',
            'tour.booking_created',
            'Tour booking created',
            bookingCode: $tour->booking_code ?? null,
            newValues: $request->only(['tour_id', 'travel_date', 'amount']),
            status: 'success',
        );

        return response()->json([
            'status' => true,
            'data' => $tour
        ]);
    }
    // app/Http/Controllers/Api/BookingController.php

    public function addVisa(StoreBookingVisaRequest $request)
    {


        $visa = $this->bookingService->createVisaBooking($request);
        if (!$visa) {
            Log::error("error");
            Log::info($visa);

            audit_log('visa', 'booking_failed', 'visa.booking_failed', 'Visa booking creation failed', status: 'failure');

            return response()->json([
                'status' => false,
                'message' => 'Visa booking was not successfully',

            ], 400);
        }

        audit_log(
            'visa',
            'booking_created',
            'visa.booking_created',
            'Visa booking created',
            bookingCode: $visa['booking_code'] ?? null,
            newValues: $request->only(['lastname', 'firstname', 'passport_number', 'nationality_id']),
            status: 'success',
        );

        return response()->json([
            'message' => 'Visa booking created successfully',
            'data' => $visa
        ], 200);
    }


    // public function addFlight(Request $request, BookingServices $bookingService)
    // {
    //     $validated = $request->validate([
    //         'bookingCode' => 'required|string',
    //         'travelerPricing' => 'required|array',
    //         'travelerPricing.travelerId' => 'required|integer',
    //         'travelerPricing.travelerType' => 'required|string',
    //         'travelerPricing.fareOption' => 'required|string',
    //         'travelerPricing.price.total' => 'required|numeric',
    //         'travelerPricing.price.base' => 'required|numeric',
    //         'traveller' => 'nullable|array|min:1',
    //         'traveller.*.firstname' => 'nullable|string',
    //         'traveller.*.surname' => 'nullable|string',
    //         'traveller.*.birth_date' => 'nullable|string',
    //         'traveller.*.passport_expiry_date' => 'nullable|string',
    //         'traveller.*.passport_nationality' => 'nullable|string',
    //         'traveller.*.phone_number' => 'nullable|string',
    //         'traveller.*.passport_issuance_date' => 'nullable|string',
    //         'traveller.*.emailaddress' => 'nullable|string',
    //         'payload' => 'required|array',
    //         'result' => 'required|string',
    //         'amadeusClientRef' => 'required|string',
    //     ]);
    //     $booking = $bookingService->createFlightBooking(
    //         $validated['bookingCode'],
    //         $validated['travelerPricing'],
    //         $validated['payload'],
    //         $validated['result'],
    //         $validated['amadeusClientRef'],
    //         $validated['traveller']

    //     );

    //     return response()->json(['success' => true]);
    // }



    public function addHotel(Request $request)
    {
        $hotel = $this->bookingService->createHotelBooking($request->all());

        audit_log(
            'hotel',
            'booking_created',
            'hotel.booking_created',
            'Hotel booking created',
            bookingCode: $request->input('booking_code'),
            newValues: $request->only(['hotel_id', 'arrival_date', 'departure_date', 'amount']),
            status: 'success',
        );

        return response()->json(['status' => true, 'data' => $hotel]);
    }


    public function addHotelGuest(Request $request)
    {
        $guest = $this->bookingService->createHotelGuest(
            $request->booking_detail_code,
            $request->booking_code,
            $request->guest_type
        );

        return response()->json(['status' => true, 'data' => $guest]);
    }


    public function updateVisa(Request $request)
    {
        $this->bookingService->updateVisaBooking($request->all());
        return response()->json([
            'message' => 'Visa booking updated successfully'
        ]);
    }

    public function updateFlight(Request $request)
    {
        $this->bookingService->updateFlightBooking($request->all());
        return response()->json([
            'message' => 'Flight booking updated successfully'
        ]);
    }

    public function getPayment($paymentCode)
    {
        $payment = $this->bookingService->getPayment($paymentCode);
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        audit_log(
            'payment',
            'lookup',
            'payment.lookup',
            'Payment record looked up',
            metadata: ['payment_code' => $paymentCode],
            status: 'success',
        );

        return response()->json($payment);
    }

    public function updateHotel(Request $request)
    {
        $reponse = $this->bookingService->updateHotelBooking($request->all());
        if (!$reponse) {
            return response()->json(['message' => 'Updating Failed'], 400);
        }
        return response()->json($reponse, 200);
    }
    public function updateTourBooking(Request $request)
    {
        $reponse = $this->bookingService->updateTourBooking($request->all());
        if (!$reponse) {
            return response()->json(['message' => 'Updating Failed'], 400);
        }
        return response()->json($reponse, 200);
    }
    public function preProcessBookingFlight($booking_code)
    {
        try {

            $booking = $this->bookingService->preProcessBookingFlight($booking_code);
            if (!$booking || !isset($booking['status'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid response from booking service'
                ], 500);
            }


            if (!$booking['status']) {
                return response()->json([
                    'status' => false,
                    'message' => $booking['message'],
                    // 'error' => $booking['error']
                ], 400);
            }

            return response()->json([

                'data' => $booking
            ], 200);
        } catch (Exception $e) {

            Log::error('PreProcess Booking Flight Error', [
                'booking_code' => $booking_code,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
