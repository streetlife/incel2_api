<?php
namespace App\Services;

use App\Models\Traveller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TravellerServices
{
    public function getTravellers($usercode)
    {
        return Traveller::where('usercode', $usercode)->get();
    }

    public function getTravellerList($usercode)
    {
        return Traveller::where('usercode', $usercode)
            ->select('usercode', 'access_code', 'travel_group', 'surname', 'firstname', 'othernames')
            ->orderBy('travel_group')
            ->orderBy('surname')
            ->orderBy('firstname')
            ->get();
    }

    public function getTravellerByAccessCode($accessCode)
    {
        return Traveller::where('access_code', $accessCode)->first();
    }

    public function createTraveller(array $data)
    {
        $userCode = auth()->user()->usercode ?? "temp" . now()->format('ymdHis');
        $data['access_code'] = 'guest_' . Str::random(10);
        $data['usercode'] =$userCode; 
        return Traveller::create($data);
    }

    public function updatePassport($usercode, $filePath)
    {
        return Traveller::where('usercode', $usercode)->update(['passport_file' => $filePath]);
    }

    public function updateTraveller($accessCode, array $data)
    {
        $traveller = Traveller::where('access_code', $accessCode)->first();
        if ($traveller) {
            $traveller->update($data);
        }
        return $traveller;
    }

    public function getUserStats($usercode): array
    {
        return [
            'invoices'   => DB::table('invoices')->where('usercode', $usercode)->count(),
            'payments'   => DB::table('payments')->where('usercode', $usercode)->count(),
            'bookings'   => DB::table('bookings')->where('usercode', $usercode)->count(),
            'travellers' => DB::table('travellers')->where('usercode', $usercode)->count(),
        ];
    }
}