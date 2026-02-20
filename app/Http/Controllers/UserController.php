<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
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
}
