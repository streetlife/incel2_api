<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\NewsletterServices;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public  function __construct(protected NewsletterServices $service) {}

    public function sendNewsletter(Request $request)
    {
        $validate = $request->validate([
            'email' => 'required|email',
        ]);
        $result = $this->service->sendNewsletter($validate['email']);
        if ($result) {
            return response()->json(['status' => true, 'message' => 'Email sent successfully', 'data' => $result], 201);
        } else {
            return response()->json(['status' => false, 'message' => 'Email not sent'], 201);
        }
    }
}
