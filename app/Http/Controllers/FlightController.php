<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingFlights;
use App\Services\BookingServices;
use App\Services\FlightServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Stmt\TryCatch;
use Symfony\Component\HttpFoundation\Response;

class FlightController extends Controller
{
    protected $FlightServices;
    protected $bookingService;


    public function __construct(FlightServices  $FlightServices, BookingServices $bookingService)
    {
        $this->FlightServices = $FlightServices;
        $this->bookingService = $bookingService;
    }

    public function bookFlight(Request $request)
    {
        $request->validate([
            'booking_code' => 'required|string|exists:bookings,booking_code',
        ]);

        try {

            $bookingCode = $request->booking_code;
            $booking = Booking::where('booking_code', $bookingCode)->first();

            if (!$booking) {
                return response()->json([
                    'status' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            $bookingFlights = BookingFlights::where('booking_code', $bookingCode)
                ->get()
                ->toArray();

            if (empty($bookingFlights)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No flights found for this booking'
                ], 404);
            }


            $pnr = $this->bookingService->preProcessBookingFlight(
                $bookingCode,
                $bookingFlights
            );

            if (!$pnr) {
                return response()->json([
                    'status' => false,
                    'message' => 'Flight booking failed'
                ], 400);
            }

            return response()->json([
                'status' => true,
                'message' => 'Flight booked successfully',
                'booking_code' => $bookingCode,
                'pnr' => $pnr
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'search_type'      => 'required|in:oneway,roundtrip,multi',
                'supplier'         => 'required|string',
                'from'             => 'required|string|size:3',
                'to'               => 'required|string|size:3',
                'adult_number'     => 'required|integer|min:1',
                'child_number'     => 'nullable|integer|min:0',
                'infants_number'   => 'nullable|integer|min:0',
                'flight_class'     => 'nullable|string',
                'flight_connection' => 'nullable|string',
                'flexible_dates'   => 'nullable|string',
                // 'dateFrom' => 'nullable|string',
                'roundtrip-date'   => 'nullable|string',
                'dateFrom' => 'nullable|string',
                'dateTo'   => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $searchType = $request->input('search_type');
            $supplier   = $request->input('supplier');

            $response = $this->FlightServices->searchFlights(
                $searchType,
                $supplier,
                $request->all()
            );
            return response()->json(['status' => true, 'message' => 'Data fetched successfully', 'data' => $response], 200);
        } catch (Exception $e) {
            return response()->json(['status'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function getFlightSession(string $session_code)
    {
        try {

            $response = $this->FlightServices->getFlightSession($session_code);
            return response()->json(['status' => true, 'message' => 'Data fetched successfully', 'data' => $response], 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
    public function getCityFromAirportCode(string $code)
    {
        try {
            $response = $this->FlightServices->getCityFromAirportCode($code);
            return response()->json(['status' => true, 'message' => 'Data fetched successfully', 'data' => $response], 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
    public function getAirline(string $iataCode)
    {
        try {

            $response = $this->FlightServices->getAirline($iataCode);
            return response()->json(['status' => true, 'message' => 'Data fetched successfully',  $response], 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
    public function searchFlights(Request $request)
    {
        try {
            $response = $this->FlightServices->searchFlightResult($request->input('session_code'));
            return response()->json(['status' => true, 'message' => 'Data fetched successfully', 'data' => $response], 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
