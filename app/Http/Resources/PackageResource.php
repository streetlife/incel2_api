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
            'poster' => secured_path($this->poster),
            'status' => $this->status,
            'picture1' => secured_path($this->picture1),
            'picture2' => secured_path($this->picture2),
            'picture3' => secured_path($this->picture3),
            'picture4' => secured_path($this->picture4),
            'banner' => secured_path($this->banner),
        ];
    }
}
