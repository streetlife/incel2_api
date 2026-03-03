<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTravellerRequest;
use App\Services\BookingServices;
use App\Services\TravellerServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TravelController extends Controller
{
    protected $service;

    public function __construct(TravellerServices $service,protected BookingServices $bookingServices)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $data = $this->service->getTravellerList($request->usercode);
        if (!$data) {
            return response()->json(['status' => false, 'message' => 'data not found'], 404);
        }
        return response()->json(['status' => true, 'message' => 'successful', 'data' => $data], 200);
    }
    public function show($access_code)
    {
        $traveller = $this->service->getTravellerByAccessCode($access_code);
        return $traveller ? response()->json(['status' => true, 'message' => 'successful', 'data' => $traveller]) : response()->json(['status' => false, 'message' => 'Not found', 'data' => []], 404);
    }
    public function stats($usercode)
    {
        $data = $this->service->getUserStats($usercode);
        if (!$data) {
            return response()->json(['status' => false, 'message' => 'data not found'], 404);
        }
        return response()->json(['status' => true, 'message' => 'successful', 'data' => $data], 200);
    }
    public function store(StoreTravellerRequest $request)
    {
        $data = $request->validated();
        if ($request->hasFile('passport_file')) {
            $data['passport_file'] = $request->file('passport_file')->store('passport');
        }
        $traveller = $this->service->createTraveller($data);
        if (!$traveller) {
            return response()->json(['status' => false, 'message' => 'failed'], 400);
        }
        return response()->json(['status' => true, 'message' => 'successful', 'data' => $traveller], 201);
    }
    public function generateBookingCode(){
       
        $userCode = auth()->user()->usercode ?? "temp" . now()->format('ymdHis');
        $bookingCode = $this->bookingServices->createBooking($userCode, 'VISA');
        if ($bookingCode === 0) {
            return response()->json(['status' => false, 'message' => 'failed'], 400);
        }
        return response()->json(['status' => true, 'message' => 'successful', 'data' => $bookingCode], 201);
    }

}

