<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'barber_id' => 'required|exists:barbers,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email',
            'customer_phone' => 'required|string|max:20',
            'start_at' => 'required|date_format:Y-m-d\TH:i:s|after_or_equal:now',
            'duration_min' => 'integer|min:15|max:180',
            'note' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'start_at.after_or_equal' => 'Az időpont nem lehet a múltban.',
            'barber_id.exists' => 'A kiválasztott borbély nem létezik.',
        ];
    }
}
