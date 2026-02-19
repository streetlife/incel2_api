<?php

namespace App\Services;

use App\Models\Country;
use App\Models\CountryIsoData;

class CountryServices
{

    public function country($countryCode)
    {
        $country = Country::where('iso_code', $countryCode)->first();
        return $country;
    }
    public function countries()
    {
        $country = Country::all();
        return $country;
    }

    public function countryIsoData()
    {
        $country = CountryIsoData::all();
        return $country;
    }

    public function getCountriesDialingCode()
    {
        return CountryIsoData::pluck(
            'country_name_english',
            'dialling_code'
        )->toArray();
    }
    public function getCountriesIso2(): array
    {
        return CountryIsoData::pluck(
            'country_name_english',
            'ISO3166_1_Alpha_2'
        )->toArray();
    }

    public function getCountriesIso3(): array
    {
        return CountryIsoData::pluck(
            'country_name_english',
            'ISO3166_1_Alpha_3'
        )->toArray();
    }

    public function getCountryIso(string $countryCode)
    {
        return CountryIsoData::where(
            'ISO3166_1_Alpha_2',
            $countryCode
        )->get();
    }
}
