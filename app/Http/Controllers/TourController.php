<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\TourServices;
use Illuminate\Http\Request;

class TourController extends Controller
{

    public function __construct(public TourServices $tourService)
    {
        
    }

    public function search(Request $request)
    {
        $request->validate([
            'country_id' => 'required|integer',
            'city_id'    => 'required|integer',
            'date'       => 'required|date',
        ]);

        $result = $this->tourService->search(
            $request->country_id,
            $request->city_id,
            $request->date
        );

        return response()->json($result);
    }
}
