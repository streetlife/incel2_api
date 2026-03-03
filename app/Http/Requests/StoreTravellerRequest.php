<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTravellerRequest extends FormRequest
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
            'surname'              => 'nullable|string|max:100',
            'firstname'            => 'nullable|string|max:100',
            'othernames'           => 'nullable|string|max:100',
            'email_address'        => 'nullable|email|max:150',
            'mobile_number'        => 'nullable|string|max:20',
            'birth_date'           => 'nullable|date',
            'gender'               => 'nullable|string',
            'nationality'          => 'nullable|string|max:5',
            'passport_number'      => 'nullable|string',
            'passport_expiry_date' => 'nullable|date',
            'passport_file'        => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'passport_country'     => 'nullable|string',
            'travel_group'         => 'nullable|string',
            'passport_issue_date'  => 'nullable|date',
            'contact_address'      => 'nullable|string',
            'contact_city'         => 'nullable|string',
            'contact_state'        => 'nullable|string',
            'contact_country'      => 'nullable|string',
            'title'                => 'nullable|string',
        ];
    }
}
