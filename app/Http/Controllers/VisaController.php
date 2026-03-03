<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\VisaServices;
use Illuminate\Http\Request;

class VisaController extends Controller
{
    protected $visaService;

    public function __construct(VisaServices $visaService)
    {
        $this->visaService = $visaService;
    }
    public function getMetadata()
    {
        $data = $this->visaService->getVisaMetadata();
        if (!$data) {
            return response()->json(['status' => false, 'message' => 'error', 'data' => $data], 400);
        }
        return response()->json(['status' => true, 'message' => 'successfull', 'data' => $data], 200);
    }
    public function showSession($code)
    {
        $session = $this->visaService->getVisaSession($code);

        if ($session->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Session not found'], 404);
        }

        return response()->json(['status' => true, 'message' => 'sucessfull', 'data' => $session], 200);
    }
    public function visaById($id)
    {
        $visa = $this->visaService->getVisa($id);
        if (!$visa) {
            return response()->json(['status' => false, 'message' => 'visa not found'], 404);
        }
        return response()->json(['status' => true, 'message' => 'successfull', 'data' => $visa], 200);
    }
}
