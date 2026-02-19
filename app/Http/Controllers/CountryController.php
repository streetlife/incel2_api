<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CountryServices;
use Exception;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public $countryService;
    public function __construct(CountryServices $countryService)
    {
        $this->countryService = $countryService;
    }

    public function dialingCodes()
    {
        try {
            return response()->json(
                $this->countryService->getCountriesDialingCode(),
                200
            );
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function iso2()
    {
        return response()->json(
            $this->countryService->getCountriesIso2(),
            200
        );
    }

    public function iso3()
    {
        return response()->json(
            $this->countryService->getCountriesIso3(),
            200
        );
    }

    public function country(Request $request)
    {
        $validated = $request->validate([
            'country_code' => 'required|string'
        ]);

        return response()->json(
            $this->countryService->Country($validated['country_code']),
            200
        );
    }

    public function countryIso(Request $request)
    {
        $validated = $request->validate([
            'country_code' => 'required|string'
        ]);

        return response()->json(
            $this->countryService->getCountryIso($validated['country_code']),
            200
        );
    }
    public function allCountries()
    {
        try {
            $reponse = $this->countryService->countries();
            return response()->json($reponse, 200);
        } catch (Exception $e) {
            return response($e->getMessage(), 500);
        }
    }
}
