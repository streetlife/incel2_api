<?php

namespace App\Services;

use App\Models\AirportTransfer;
use App\Models\Contact;
use App\Models\InsuranceServiceRequest;
use App\Models\Package;

class RequestServices
{

    public function createInsuranceService($data)
    {

        if (empty($data['terms_and_conditions']) || $data['terms_and_conditions'] == false) {
            throw new \Exception('Please accept the terms and conditions');
        }
        $userCode = auth()->user()->usercode ?? "temp" . now()->format('ymdHis');
        $data['usercode'] = $userCode;
        $data['status'] = 'pending';
        $data['date_created'] = now();
        $data['date_updated'] = now();
        $data['service'] = 'insurance';
        $data['booking_code'] = $data['booking_code'] ?? null;
        $result  = InsuranceServiceRequest::create($data);
        if (!$result) {
            throw new \Exception('Error creating insurance service request');
        }
        return $result;
    }

    public function airportTransfer($data){
        if (empty($data['terms_and_conditions']) || $data['terms_and_conditions'] == false) {
            throw new \Exception('Please accept the terms and conditions');
        }
        $userCode = auth()->user()->usercode ?? "temp" . now()->format('ymdHis');
        $data['usercode'] = $userCode;
        $data['status'] = 'pending';
        $data['date_created'] = now();
        $data['date_updated'] = now();
        $data['service'] = 'airport';
        $result = AirportTransfer::create($data);
        if (!$result) {
            throw new \Exception('Error creating airport transfer request');
        }
        return $result;
    }
     public function getTravelPackages($mode = 'all')
    {
        if ($mode === 'recent') {
            return Package::where('status', 1)
                ->orderByDesc('id')
                ->limit(6)
                ->get();
        }

        return Package::where('status', 1)->get();
    }
    public function searchPackages($countryCode)
    {
        return Package::where('country_code', $countryCode)
            ->where('status', 1)
            ->get();
    }
     public function createContact(array $data)
    {
        $contact = Contact::create($data);

        if (!$contact) {
            throw new \Exception('Unable to save contact message');
        }

        // Mail::raw(
        //     "Name: {$data['name']}\nEmail: {$data['email']}\nPhone: {$data['mobile_phone']}\nMessage: {$data['message']}",
        //     function ($message) use ($data) {
        //         $message->to(config('mail.from.address'))
        //                 ->subject('New Contact Message');
        //     }
        // );

        return $contact;
    }
}
