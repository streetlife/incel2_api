<?php

namespace App\Services;

use App\Http\Resources\PackageResource;
use App\Models\AirportProtocol;
use App\Models\AirportTransfer;
use App\Models\Contact;
use App\Models\HeroSection;
use App\Models\HotDeal;
use App\Models\Hotel;
use App\Models\InsuranceServiceRequest;
use App\Models\Package;
use App\Models\Partners;
use App\Models\Stats;
use App\Models\TourGuide;
use App\Models\VideoTestimonial;

class RequestServices
{

    public function __construct(protected CloudinaryServices $cloudinary) {}
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

    public function airportTransfer($data)
    {
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
            $data = Package::where('status', 1)
                ->orderByDesc('id')
                ->limit(6)
                ->get();
            return PackageResource::collection($data);
        }

        $data = Package::where('status', 1)->get();
        return  PackageResource::collection($data);
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
    public function airportProtocol($data)
    {
        if (empty($data['terms_and_conditions']) || $data['terms_and_conditions'] == false) {
            throw new \Exception('Please accept the terms and conditions');
        }

        $result = AirportProtocol::create($data);
        if (!$result) {
            throw new \Exception('Error creating airport protocol');
        }
        return $result;
    }
    public  function tourGuide($data)
    {
        if (empty($data['terms_and_conditions']) || $data['terms_and_conditions'] == false) {
            throw new \Exception('Please accept the terms and conditions');
        }
        $result = TourGuide::create($data);
        if (!$result) {
            throw new \Exception('Error creating tour guide request');
        }
        return $result;
    }
    public function hotDeals($data)
    {
        return HotDeal::create($data);
    }
    public function getHotdeals()
    {
        return HotDeal::all();
    }
    public function getHotdealslById($id)
    {
        $data = HotDeal::findorFail($id);
        return $data;
    }
    public function getTravelPackageById($id)
    {
        $data = Package::findorFail($id);
        return $data;
    }
    public function updateTravelPackage($data, $id, $files = [])
    {
        $package = Package::findOrFail($id);


        $fields = ['picture1', 'picture2', 'picture3', 'picture4', 'banner', 'poster'];

        foreach ($fields as $field) {
            if (isset($files[$field])) {
                $upload = $this->cloudinary->upload($files[$field], 'package');
                $package->$field = $upload['url'];
            }
        }


        foreach ($data as $key => $value) {
            $package->$key = $value;
        }


        $package->save();

        return $package;
    }
    public function addStats($data)
    {
        $data = new Stats($data);
        $data->save();
        return $data;
    }
    public function getStats()
    {
        return Stats::first();
    }
    public function getAllPartner()
    {
        return Partners::all();
    }
    public function createHeroSection(array $data, $videoFile = null)
    {
        $videoUrl = null;
        $publicId = null;

        if ($videoFile) {

            $existing = HeroSection::first();
            if ($existing && $existing->public_id) {
                $this->cloudinary->delete($existing->public_id);
            }

            $upload = $this->cloudinary->uploadVideo($videoFile, 'heroSection');

            $videoUrl = $upload['url'];
            $publicId = $upload['public_id'];
        }

        return HeroSection::updateOrCreate(
            ['id' => 1],
            [
                'title'       => $data['title'],
                'description' => $data['description'],
                'video_url'   => $videoUrl ?? HeroSection::first()?->video_url,
                'public_id'   => $publicId ?? HeroSection::first()?->public_id,
            ]
        );
    }

    public function getHeroSection()
    {
        return HeroSection::first();
    }
    public function createVideoTestmonial(array $data, $videoFile = null)
    {
        $videoUrl = null;
        $publicId = null;

        if ($videoFile) {
            $upload = $this->cloudinary->uploadVideo($videoFile, 'VideoTestimonial');

            $videoUrl = $upload['url'];
            $publicId = $upload['public_id'];
        }

        return VideoTestimonial::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'country'    => $data['country'],
            'rating'     => $data['rating'],
            'comment'    => $data['comment'],
            'video_url'  => $videoUrl,
            'public_id'  => $publicId,
        ]);
    }
}
