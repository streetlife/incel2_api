<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BookingServices;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    protected $bookingService;

    public function __construct(BookingServices $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function create(Request $request)
    {
        $request->validate([
            'usercode' => 'required|string',
            'booking_type' => 'required|string'
        ]);

        $booking = $this->bookingService->createBooking(
            $request->usercode,
            $request->booking_type
        );

        return response()->json([
            'status' => true,
            'data'   => $booking
        ]);
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
        $request->validate([
            'booking_code'        => 'required|string',
            'booking_detail_code' => 'required|string',
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
        ]);

        $tour = $this->bookingService->createTourBooking($request->all());

        return response()->json([
            'status' => true,
            'data' => $tour
        ]);
    }
    // app/Http/Controllers/Api/BookingController.php

    public function addVisa(Request $request)
    {
        $request->validate([
            'booking_code' => 'required|string',
            'visa_id' => 'required|integer'
        ]);

        $visa = $this->bookingService->createVisaBooking(
            $request->booking_code,
            $request->visa_id
        );

        return response()->json(['status' => true, 'data' => $visa]);
    }


    public function addFlight(Request $request, BookingServices $bookingService)
{
    $validated = $request->validate([
        'bookingCode' => 'required|string',
        'travelerPricing' => 'required|array',
        'travelerPricing.travelerId' => 'required|integer',
        'travelerPricing.travelerType' => 'required|string',
        'travelerPricing.fareOption' => 'required|string',
        'travelerPricing.price.total' => 'required|numeric',
        'travelerPricing.price.base' => 'required|numeric',
        'payload' => 'required|array',
        'result' => 'required|string',
        'amadeusClientRef' => 'required|string',
    ]);
    $booking = $bookingService->createFlightBooking(
        $validated['bookingCode'],
        $validated['travelerPricing'],
        $validated['payload'],
        $validated['result'],
        $validated['amadeusClientRef']
    );

    return response()->json(['success' => true]);
}



    public function addHotel(Request $request)
    {
        $hotel = $this->bookingService->createHotelBooking($request->all());

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
        return response()->json($payment);
    }

    public function updateHotel(Request $request){
      $reponse = $this->bookingService->updateHotelBooking($request->all());
      if (!$reponse) {
        return response()->json(['message' => 'Updating Failed'], 400);
      }
      return response()->json($reponse, 200);
    }
      public function updateTourBooking(Request $request){
        $reponse = $this->bookingService->updateTourBooking($request->all());
        if (!$reponse) {
          return response()->json(['message' => 'Updating Failed'], 400);
        }
        return response()->json($reponse,200);
      }
    
}
