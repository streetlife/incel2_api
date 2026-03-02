<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingVisaRequest extends FormRequest
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
            'lastname'                => 'required|string|max:255',
            'firstname'               => 'required|string|max:255',
            'othernames'              => 'nullable|string|max:255',

            'passport_expiry_date'    => 'required|date',
            'passport_country'        => 'required|string|max:10',
            'passport_number'         => 'required|string|max:50',
            'passport_issuance_date'  => 'required|date',

            'email_address'           => 'required|email',
            'birth_date'               => 'required|date',

            // FILES
            'passport_photo'          => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'passport_data_page'      => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ];
    }

     public function messages(): array
    {
        return [
            'passport_photo.required'     => 'Passport photo is required',
            'passport_data_page.required' => 'Passport data page is required',
        ];
    }
}
