<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\userServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function __construct(public userServices $userServices) {}

    public function getUserByUserCode($userCode)
    {
        try {
            $data = $this->userServices->getUserByUserCode($userCode);
            return response()->json([
                'message' => 'success',
                'data' => $data
            ], 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }
    public function getAuthenticatedUserByEmail()
    {
        try {
            $data = $this->userServices->getAuthenticatedUserByEmail();
            return response()->json([
                'message' => 'success',
                'data' => $data
            ], 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }
    public function index()
    {
        $reviews = Review::latest()->get();

        return response()->json([
            'status' => true,
            'message' => 'Reviews fetched successfully',
            'data' => $reviews
        ], 200);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'rating'     => 'required|numeric|min:1|max:5',
            'review'     => 'required|string',
            'country'    => 'required|string|max:255',
        ]);

        $review = Review::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Review created successfully',
            'data' => $review
        ], 201);
    }
}
