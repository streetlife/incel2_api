<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\FlightServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Stmt\TryCatch;
use Symfony\Component\HttpFoundation\Response;

class FlightController extends Controller
{
    protected $FlightServices;

    public function __construct(FlightServices  $FlightServices)
    {
        $this->FlightServices = $FlightServices;
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
                'daterange-single' => 'nullable|string',
                'roundtrip-date'   => 'nullable|string',
                'roundtrip-datefrom' => 'nullable|string',
                'roundtrip-dateto'   => 'nullable|string',
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
            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function getFlightSession(string $session_code)
    {
        try {
          
            $response = $this->FlightServices->getFlightSession($session_code);
            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
    public function getCityFromAirportCode( string $code)
    {
        try {
            $response = $this->FlightServices->getCityFromAirportCode($code);
            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
    public function getAirline(string $iataCode)
    {
        try {
        
            $response = $this->FlightServices->getAirline($iataCode);
            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
    public function searchFlights(Request $request)
    {
        try {
            $response = $this->FlightServices->searchFlightResult($request->input('session_code'));
            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
