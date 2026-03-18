<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAirportTransferRequest;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Resources\HotdealResource;
use App\Http\Resources\PackageResource;
use App\Models\Package;
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
    public function update(Request $request, $id)
    {
        
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'description' => 'sometimes|string',
            'picture1' => 'nullable|file|image',
            'picture2' => 'nullable|file|image',
            'picture3' => 'nullable|file|image',
            'picture4' => 'nullable|file|image',
            'banner'   => 'nullable|file|image',
            'poster'   => 'nullable|file|image',
        ]);

        
        $files = [
            'picture1' => $request->file('picture1'),
            'picture2' => $request->file('picture2'),
            'picture3' => $request->file('picture3'),
            'picture4' => $request->file('picture4'),
            'banner'   => $request->file('banner'),
            'poster'   => $request->file('poster'),
        ];

        
        $package = $this->service->updateTravelPackage(
            $validated,
            $id,
            $files
        );

        return response()->json([
            'status' => true,
            'message' => 'Package updated successfully',
            'data' => $package
        ]);
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
            'data' => HotdealResource::collection($hotdeals)
        ], 200);
        if (!$hotdeals) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }
    public function getHotdealsById($id)
    {
        $hotdeals = $this->service->getHotdealslById($id);
        if ($hotdeals) {
            return response()->json([
                'status' => true,
                'message' => 'Succesessful',
                'data' => new HotdealResource($hotdeals)
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }
    public function getTravelPackageById($id)
    {
        $travelPackage = $this->service->getTravelPackageById($id);
        if ($travelPackage) {
            return response()->json([
                'status' => true,
                'message' => 'Succesessful',
                'data' => new PackageResource($travelPackage)
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    public function addStats(Request $request)
    {
        $data = $this->service->addStats($request->all());
        if ($data) {
            return response()->json([
                'status' => true,
                'message' => 'Successsful',
                'data' => $data
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }
    public function getStats()
    {
        $stats = $this->service->getStats();
        return response()->json([
            'status' => true,
            'message' => 'Successsful',
            'data' => $stats
        ], 200);
        if (!$stats) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }
    public function getAllPartner()
    {
        $partners = $this->service->getAllPartner();
        return response()->json([
            'status' => true,
            'message' => 'Successsful',
            'data' => $partners
        ], 200);
        if (!$partners) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    public function createHeroSection(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'video' => 'nullable|file|mimes:mp4,mov,avi|max:51200',
        ]);

        $result = $this->service->createHeroSection(
            $validated,
            $request->file('video')
        );
        if (!$result) {
            return response()->json(['status' => false, 'message' => 'Something went wrong'], 500);
        }
        return response()->json([
            'status' => true,
            'message' => 'hero video created successfully',
            'data' =>  $result
        ], 201);
    }
    public function getHeroSection()
    {
        $heroSection = $this->service->getHeroSection();
        if ($heroSection) {
            return response()->json([
                'status' => true,
                'message' => 'Successsful',
                'data' => $heroSection
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }
    public function createVideoTestmonial(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'country'    => 'required|string|max:255',
            'rating'     => 'required|numeric|min:1|max:5',
            'comment'    => 'required|string',
            'video'      => 'nullable|file|mimes:mp4,mov,avi|max:51200',
        ]);
        $review = $this->service->createVideoTestmonial(
            $validated,
            $request->file('video')
        );

        return response()->json([
            'status' => true,
            'message' => 'Video testmonials created successfully',
            'data' => $review
        ], 201);
    }
}
