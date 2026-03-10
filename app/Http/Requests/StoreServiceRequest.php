<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'booking_code' => ['nullable', 'string', 'max:255'],
            'service' => ['required', 'string', 'max:14'],
            'status' => ['required', 'string', 'max:100'],
            'request_details' => ['nullable', 'array', 'min:1'],

            'request_details.*.name' => ['required', 'string', 'max:100'],
            'request_details.*.email' => ['required', 'email', 'max:100'],
            'request_details.*.phone' => ['required', 'string', 'max:100'],
            'request_details.*.address' => ['required', 'string', 'max:200'],
            'request_details.*.occupation' => ['required', 'string', 'max:200'],
            'request_details.*.maritalStatus' => ['required', 'string', 'max:200'],

            'request_details.*.nextOfKin' => ['required', 'string', 'max:100'],
            'request_details.*.nextOfKinPhone' => ['required', 'string', 'max:100'],
            'request_details.*.nextOfKinAddress' => ['required', 'string', 'max:200'],
            'request_details.*.nextOfKinContact' => ['required', 'string', 'max:200'],
            'request_details.*.nextOfKinRelationship' => ['required', 'string', 'max:200'],

            'request_details.*.coverStartDate' => ['nullable', 'date'],
            'request_details.*.coverEndDate' => ['nullable', 'date'],

            'request_details.*.destination' => ['required', 'string', 'max:200'],
            'request_details.*.passengerStateOfResident' => ['required', 'string', 'max:100'],
            'request_details.*.additionalInformation' => ['required', 'string', 'max:200'],

            'terms_and_conditions' => ['nullable', 'Boolean', 'max:200'],
        ];
    }
}
