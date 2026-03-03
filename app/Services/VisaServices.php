<?php

namespace App\Services;


use App\Models\VisaPrice;
use Illuminate\Support\Facades\DB;


class VisaServices extends FlightServices
{
    public function getVisaMetadata()
    {
        return [
            'group_memberships' => DB::table('visas_groupmembership')->get(['groupmembership as value', 'groupmembership_id as label']),
            'languages'         => DB::table('visas_language')->get(['language as value', 'language_id as label']),
            'marital_statuses'  => DB::table('visas_maritalstatus')->get(['maritalstatus as value', 'maritalstatus_id as lebel']),
            'passport_types'    => DB::table('visas_passporttypes')->get(['passport_type as value', 'passport_type_id as lebel']),
            'processings'       => DB::table('visas_processing')->get(['processing as value', 'processing_id as lebel']),
            'professions'       => DB::table('visas_profession')->get(['profession as value', 'profession_id as lebel']),
            'religions'         => DB::table('visas_religion')->get(['religion as value', 'religion_id as lebel']),
            'visa_types'        => DB::table('visas_visatypes')->get(['visa_type as value', 'visa_type_id as lebel']),
            'visa_countries' => DB::table('visas_countries')->orderBy('itemsname')->get(['itemsid as lebel', 'itemsname as value ','itemsiso_code as code']),
            'visa_gender_types' => DB::table('visas_gendertypes')->get(['gendertype as value', 'gendertype_id as lebel']),
            'visa_prices' => DB::table('visa_prices')->get(['destination_country as value','id as label'])
        ];
    }

    public function getVisa($id)
    {
        $data = VisaPrice::where('id', $id)->first();
        return $data;
    }
    public function getVisaSession(string $sessionCode)
    {
        return DB::table('sessions_visas as v')
            ->join('countries as c', 'v.country_destination', '=', 'c.iso_code')
            ->select('v.*', 'c.country')
            ->where('v.session_code', $sessionCode)
            ->get();
    }
    public function createVisaSearchSession(array $data): string
    {
        $sessionCode = 'visa' . uniqid();
        $countryDest = $data['country_destination'];
        $countryNat = $data['country_nationality'];


        $visaPrices = DB::table('visa_prices')
            ->where('destination_country', $countryDest)
            ->where(function ($query) use ($countryNat) {
                $query->where('nationality', $countryNat)
                    ->orWhereNull('nationality');
            })
            ->get();


        if ($visaPrices->isEmpty()) {
            $newId = DB::table('visa_prices')->insertGetId([
                'visa_name' => 'Visa Assistance',
                'days' => 30,
                'destination_country' => $countryDest,
                'currency_code' => 'AED',
                'price' => 0
            ]);


            $visaPrices = DB::table('visa_prices')->where('id', $newId)->get();
        }


        foreach ($visaPrices as $price) {
            DB::table('sessions_visas')->insert([
                'session_code' => $sessionCode,
                'country_destination' => $countryDest,
                'country_nationality' => $countryNat,
                'adults' => $data['adult_number'] ?? 1,
                'currency_code' => $price->currency_code,
                'price' => $price->price,
                'visa_name' => $price->visa_name,
                'product_id' => $price->id,
            ]);
        }

        return $sessionCode;
    }
    public function getVisaBySession(string $session_code): array
    {
        $sessions = DB::table('sessions_visas as v')
            ->join('countries as c', 'v.country_destination', '=', 'c.iso_code')
            ->where('v.session_code', $session_code)
            ->select(
                'v.id',
                'v.product_id',
                'v.visa_name',
                'v.adults',
                'v.children',
                'v.price',
                'v.currency_code',
                'v.country_destination',
                'c.country'
            )
            ->get();

        if (!$sessions) {
            return [
                'status' => false,
                'message' => 'No session found',
                'code' => 404
            ];
        }

        $markup = $this->getMarkup('VISA');

        $results = [];

        foreach ($sessions as $session) {

            $price = $this->priceMarkup($session->price, $markup);

            $amountAdults = $session->adults * $price;
            $amountChildren = $session->children * $price;
            $amountTotal = $amountAdults + $amountChildren;

            $results[] = [
                'id' => $session->id,
                'product_id' => $session->product_id,
                'visa_name' => $session->visa_name,
                'country' => $session->country,
                'adults' => $session->adults,
                'children' => $session->children,
                'price' => $price,
                'currency_code' => $session->currency_code,
                'amount_adults' => $amountAdults,
                'amount_children' => $amountChildren,
                'amount_total' => $amountTotal,
            ];
        }

        return [
            'status' => true,
            'message' => 'Visa session retrieved successfully',
            'code' => 200,
            'data' => [
                'session_code' => $session_code,
                'country' => $sessions[0]->country,
                'results' => $results
            ]
        ];
    }
}
