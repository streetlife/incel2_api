<?php

namespace App\Services;

use App\Http\Resources\AboutusResource;
use App\Models\AboutUs;

class AboutusServices
{

    public function createAbout($data)
    {
        $data = $this->handleImages($data);
        return AboutUs::create($data);
    }

    protected function handleImages($data)
    {
        if (isset($data['banner_image'])) {
            $data['banner_image'] = $this->uploadImage($data['banner_image']);
        }

        if (isset($data['story_image'])) {
            $data['story_image'] = $this->uploadImage($data['story_image']);
        }

        return $data;
    }

    protected function uploadImage($image)
    {
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $image->move(public_path('uploads/about'), $imageName);
        return  secured_path('uploads/about/' . $imageName);
    }

    public function getAllAbout()
    {
        $data = $data = AboutUs::first();
        return new AboutusResource($data);
    }
}
