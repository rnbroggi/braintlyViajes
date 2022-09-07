<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FlightRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'occupants' => 'required|integer',
            'departure_airport' => 'required|exists:airports,iata_code',
            'arrival_airport' => 'required|exists:airports,iata_code',
            'check_in' => 'required|date',
            'check_out' => 'required|date',
            'type' => ['required', 'string', Rule::in(['economic', 'firstclass'])],
        ];
    }
}
