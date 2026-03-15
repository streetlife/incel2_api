<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AboutusResource extends JsonResource
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
            'banner_title' => $this->banner_title,
            'banner_description' => $this->banner_description,
            'banner_image' => $this->banner_image,
            'company_story' => [
                'story' => $this->story,
                'story_image' => $this->story_image,
            ],
            'mission' => $this->our_mission,
            'core_values' => json_decode($this->core_value,false),
            'our_promise' => json_decode($this->our_promise,false),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,



        ];
    }
}
