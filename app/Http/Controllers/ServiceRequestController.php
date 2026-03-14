<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAirportTransferRequest;
use App\Http\Requests\StoreServiceRequest;
use App\Services\RequestServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SebastianBergmann\Environment\Console;

class ServiceRequestController extends Controller
{
    public function __construct(protected RequestServices $service) {}

    public function createInsuranceService(StoreServiceRequest $request)
    {
        try {

            $data = $request->validated();

            $result = $this->service->createInsuranceService($data);

            return response()->json([
                'status' => true,
                'message' => 'Insurance service request created successfully',
                'data' => $result
            ], 201);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => "Something went wrong",
            ], 400);
        }
    }


    public function airportTransfer(StoreAirportTransferRequest $request)
    {
        try {
            $data = $request->validated();

            $result = $this->service->airportTransfer($data);

            return response()->json([
                'status' => true,
                'message' => 'Airport transfer request created successfully',
                'data' => $result
            ], 201);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
            ], 400);
        }
    }
    public function getTravelPackages(Request $request)
    {
        try {

            $mode = $request->query('mode', 'all');

            $packages = $this->service->getTravelPackages($mode);

            return response()->json([
                'status' => true,
                'data' => $packages
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function searchPackages($country_code)
    {
        try {

            $packages = $this->service->searchPackages($country_code);

            return response()->json([
                'status' => true,
                'data' => $packages
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function store(Request $request)
    {
        try {

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:100'],
                'mobile_phone' => ['required', 'string', 'max:20'],
                'message' => ['required', 'string'],
                'subject' => ['required', 'string'],
            ]);

            $contact = $this->service->createContact($validated);

            return response()->json([
                'status' => true,
                'message' => 'Message sent successfully',
                'data' => $contact
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function airportProtocol(Request $request)
    {
        $data = $request->all();
        $result = $this->service->airportProtocol($data);
        return response()->json([
            'status' => true,
            'message' => 'Airport protocol request created successfully',
            'data' => $result
        ], 201);
        if (!$result) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }
    public function tourGuide(Request $request)
    {
        $data = $request->all();
        $result = $this->service->tourGuide($data);
        return response()->json([
            'status' => true,
            'message' => 'Tour guide request created successfully',
            'data' => $result
        ], 201);
        if (!$result) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }
    public function hotDeals(Request $request)
    {
        $data = $request->all();
        $result = $this->service->hotDeals($data);
        return response()->json([
            'status' => true,
            'message' => 'Hot deals request created successfully',
            'data' => $result
        ], 201);
        if (!$result) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }
    public function getHotdeals()
    {
        $hotdeals = $this->service->getHotdeals();
        return response()->json([
            'status' => true,
            'message' => 'Successsful',
            'data' => $hotdeals
        ], 200);
        if (!$hotdeals) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }
}
