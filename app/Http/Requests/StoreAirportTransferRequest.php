<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Type\Integer;

class StoreAirportTransferRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
            'request_details.*.numberOfPassenger' => ['required', 'integer', 'min:1'],
            'request_details.*.additionalInformation' => ['required', 'string', 'max:200'],
            'request_details.*.pickUpAndDropOff' => ['required', 'string', 'max:100'],
            'terms_and_conditions' => ['nullable', 'Boolean', 'max:200'],
        ];
    }
}
