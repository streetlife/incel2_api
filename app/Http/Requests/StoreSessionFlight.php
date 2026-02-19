<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSessionFlight extends FormRequest
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
            'session_code' => 'required|string|max:255',
            'amadeus_client_ref' => 'nullable|string|max:255',
            'search_type' => 'nullable|string|max:100',
            'payload' => 'required',
            'response' => 'required',
        ];
    }
}
