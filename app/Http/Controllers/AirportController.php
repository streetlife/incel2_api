<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AirportServices;
use Illuminate\Http\Request;

class AirportController extends Controller
{

    public function __construct(private AirportServices $airportService) {}
    public function airports()
    {
        $data =  $this->airportService->getAirports();
        return response()->json(['status' => true, 'message' => 'Successfully fetched data', 'data' => $data], 200);
    }

    public function airport($code)
    {
        $data = $this->airportService->getAirport($code);
        return response()->json(['status' => true, 'message' => 'Successfully fetched data', 'data' => $data], 200);
    }

    public function airportServices(Request $request)
    {
        $validated = $request->validate([
            'airport_code' => 'required|string',
            'direction' => 'required|string'
        ]);

        return response()->json(
            $this->airportService->getAirportServices(
                $validated['airport_code'],
                $validated['direction']
            ),
            200
        );
    }

    public function airportCountry(Request $request)
    {
        $validated = $request->validate([
            'airport_code' => 'required|string'
        ]);

        return response()->json(
            $this->airportService->getAirportCountry(
                $validated['airport_code']
            ),
            200
        );
    }
    public function search(Request $request)
    {
        $query = $request->query('q');

        if (!$query) {
            return response()->json([
                'message' => 'Search query is required'
            ], 400);
        }

        $results = $this->airportService->searchAirports($query);

        return response()->json(['status'=>true,'message'=>'Successfully fetched data','data'=>$results],200);
    }
}
