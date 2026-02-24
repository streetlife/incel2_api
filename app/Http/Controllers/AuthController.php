<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'email_address'     => 'required|email|unique:users,email_address',
            'password'          => 'required|confirmed',
            'full_names'        => 'required|string|max:255',
            'mobile_number'     => 'nullable|string|max:20',
        ]);

        try {

            $user = User::create([
                'usercode'          => $this->generateUserCode(),
                'full_names'        => $request->full_names,
                'email_address'     => $request->email_address,
                'mobile_number'     => $request->mobile_number,
                'password'          => Hash::make($request->password),
                'access_level'      => 'CUSTOMER',
                'activation_status' => false,
                'registration_date' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data'    => $user
            ], 201);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function generateUserCode(): string
    {
        return 'U' . now()->format('ymdHis') . rand(10, 99);
    }
    public function checkUser(Request $request)
    {
        $request->validate([
            'email_address' => 'required|email'
        ]);

        $exists = User::FindByEmail($request->email_address)->exists();

        return response()->json([
            'success' => true,
            'exists'  => $exists
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email_address' => 'required|email',
            'password'      => 'required'
        ]);

        $user = User::where('email_address', $request->email_address)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }
         $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'usercode'     => $user->usercode,
                'full_names'   => $user->full_names,
                'email'        => $user->email_address,
                'access_level' => $user->access_level,
                'token'        => $token
            ]
        ]);
    }
    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|confirmed',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }


        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Old password is incorrect'
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();
        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
