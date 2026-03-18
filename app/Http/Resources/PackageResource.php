<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'package_name' => $this->package_name,
            'country_code' => $this->country_code,
            'description' => $this->description,
            'inclusions' => $this->inclusions,
            'exclusions' => $this->exclusions,
            'terms' => $this->terms,
            'location' => $this->location,
            'category' => $this->category,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'poster' => $this->poster ? 'https://incelgroup.com/incel2_api/public/api/' . $this->poster : $this->poster,
            'status' => $this->status,
            'picture1' => $this->picture1 ? 'https://incelgroup.com/incel2_api/public/api/' . $this->picture1 : $this->picture1,
            'picture2' => $this->picture2 ? 'https://incelgroup.com/incel2_api/public/api/' . $this->picture2 : $this->picture2,
            'picture3' => $this->picture3 ? 'https://incelgroup.com/incel2_api/public/api/' . $this->picture3 : $this->picture3 ,
            'picture4' => $this->picture4 ? 'https://incelgroup.com/incel2_api/public/api/' . $this->picture4 : $this->picture4,
            'banner' => $this->banner ? 'https://incelgroup.com/incel2_api/public/api/' . $this->banner : $this->banner,
        ];
    }
}
