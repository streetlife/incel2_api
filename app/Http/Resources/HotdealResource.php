<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HotdealResource extends JsonResource
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
            'title' => $this->title,
            'deal_includes' => $this->deal_includes,
            'price' => round((float) $this->price, 1),
            'other_info' => $this->other_info,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];
    }
}
