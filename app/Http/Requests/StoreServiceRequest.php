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
            'request_details' => ['nullable'],

            'terms_and_conditions' => ['nullable', 'Boolean', 'max:200'],
        ];
    }
}
