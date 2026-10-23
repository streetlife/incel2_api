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

            audit_log(
                'auth',
                'register',
                'auth.register',
                'New user registered',
                subjectType: 'user',
                subjectId: $user->usercode,
                newValues: $user->only(['usercode', 'full_names', 'email_address', 'mobile_number']),
                status: 'success',
            );

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data'    => $user
            ], 201);
        } catch (\Exception $e) {

            audit_log('auth', 'register', 'auth.register', 'User registration failed', status: 'failure');

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
            audit_log('auth', 'login', 'auth.login', 'Login attempt for unknown user', status: 'failure');

            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            audit_log(
                'auth',
                'login',
                'auth.login',
                'Login attempt with invalid credentials',
                subjectType: 'user',
                subjectId: $user->usercode,
                status: 'failure',
            );

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }
        $token = $user->createToken('auth_token')->plainTextToken;

        audit_log(
            'auth',
            'login',
            'auth.login',
            'User logged in',
            subjectType: 'user',
            subjectId: $user->usercode,
            status: 'success',
        );

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

        audit_log(
            'auth',
            'change_password',
            'auth.change_password',
            'User changed password',
            subjectType: 'user',
            subjectId: $user->usercode,
            status: 'success',
        );

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }
    public function logout(Request $request)
    {
        $user = $request->user();

        $request->user()->currentAccessToken()->delete();

        audit_log(
            'auth',
            'logout',
            'auth.logout',
            'User logged out',
            subjectType: 'user',
            subjectId: $user?->usercode,
            status: 'success',
        );

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
    public function getProfile(Request $request)
    {
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully',
            'data' => $user
        ]);
    }
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();

            $user->full_names = $request->full_names;
            $user->mobile_number = $request->mobile_number;
            $user->save();

            return response()->json([
                'status'  => true,
                'message' => 'Profile updated successfully',
                'data'    => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to update profile',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    
}
