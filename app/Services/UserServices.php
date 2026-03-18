<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class userServices
{

    public function getUserByUserCode($userCode)
    {

        $user = User::where('usercode', $userCode)->first();
        return $user;
    }

    public function getAuthenticatedUserByEmail()
    {
        $auth_user = Auth::user();
        $user = User::where('email_address', $auth_user->email_address)->first();
        return $user;
    }
    public function 
}
