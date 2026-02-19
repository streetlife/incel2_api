<?php

namespace App\Services;

use App\Models\Airports;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AirportServices
{
   public function getAirports(): array
{
    return Cache::remember('get_airports', 3600, function () {

        $airports = Airports::join('countries', 'countries.iso_code', '=', 'airports.code')
            ->orderBy('countries.id')
            ->orderBy('airports.code')
            ->select(
                'countries.country as country_name',
                'countries.iso_code as country_iso_code',
                'airports.code',
                'airports.name'
            )
            ->get();

        $groupedAirports = [];

        foreach ($airports as $airport) {
            $groupedAirports[$airport->country_name][] = [
                'code' => $airport->code,
                'name' => $airport->name
            ];
        }

        return $groupedAirports;
    });
}


    public function getAirport(string $code)
    {
        return Cache::remember("airport_$code", 3600, function () use ($code) {
            return Airports::where('code', $code)
                ->select(DB::raw("CONCAT(name, ', ', cityname) as name"))
                ->value('name');
        });
    }

    public function getAirportServices(string $airportCode, string $direction)
    {
        return Cache::remember(
            "airport_services_{$airportCode}_{$direction}",
            3600,
            function () use ($airportCode, $direction) {
                return DB::table('airport_services as a')
                    ->join('services as s', 'a.service_id', '=', 's.id')
                    ->where('a.airport_code', $airportCode)
                    ->where('a.travel_direction', $direction)
                    ->where('a.active', 1)
                    ->select('a.*', 's.service', 's.description')
                    ->get();
            }
        );
    }
    public function getAirportCountry(string $countryCode)
    {
        return Cache::remember(
            "airport_country",
            3600,
            function () use ($countryCode) {
                return Airports::where('countryCode', $countryCode)
                    ->value('countryCode');
            }
        );
    }
}
