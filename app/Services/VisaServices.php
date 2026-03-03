<?php

namespace App\Services;

use App\Models\VisaGenderType;
use App\Models\VisaPrice;
use Illuminate\Support\Facades\DB;

class VisaServices
{
    public function getVisaMetadata()
    {
        return [
            'group_memberships' => DB::table('visas_groupmembership')->pluck('groupmembership', 'groupmembership_id'),
            'languages'         => DB::table('visas_language')->pluck('language', 'language_id'),
            'marital_statuses'  => DB::table('visas_maritalstatus')->pluck('maritalstatus', 'maritalstatus_id'),
            'passport_types'    => DB::table('visas_passporttypes')->pluck('passport_type', 'passport_type_id'),
            'processings'       => DB::table('visas_processing')->pluck('processing', 'processing_id'),
            'professions'       => DB::table('visas_profession')->pluck('profession', 'profession_id'),
            'religions'         => DB::table('visas_religion')->pluck('religion', 'religion_id'),
            'visa_types'        => DB::table('visas_visatypes')->pluck('visa_type', 'visa_type_id'),
            'visa_countries' => DB::table('visas_countries')->orderBy('itemsname')->pluck('itemsid', 'itemsname'),
            'visa_gender_types' => DB::table('visas_gendertypes')->pluck('gendertype', 'gendertype_id'),

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
}
