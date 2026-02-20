<?php

namespace App\Services;

class TourServices {

public function __construct( public RaynaServices $raynaService)

{

}    
   public function search(int $countryId, int $cityId, string $date): array
    {
        $payload = [
            'countryId'  => $countryId,
            'cityId'     => $cityId,
            'travelDate' => $date,
        ];

        return $this->raynaService->post('tourlist/', $payload);
    }

}