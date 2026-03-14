<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AboutusServices;
use Illuminate\Http\Request;

class AboutusController extends Controller
{
    public function __construct(protected AboutusServices $aboutusServices) {}

    public function create(Request $request)
    {
        try {
            //code...
            $data = $this->aboutusServices->createAbout($request->all());
            return response()->json(['stauts' => true, 'message' => 'Successfully', 'data' => $data], 201);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['stauts' => false, 'message' => 'Something went wrong'], 500);
        }
    }

    public function getAll()
    {
        try {

            $data = $this->aboutusServices->getAllAbout();
            return response()->json(['stauts' => true, 'message' => 'Successfully', 'data' => $data], 200);
        } catch (\Throwable $th) {

            return response()->json(['stauts' => false, 'message' => 'Something went wrong'], 500);
        }
    }
}
