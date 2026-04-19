<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests;

use App\Domain\Booking\ValueObjects\BookingPeriod;
use Illuminate\Foundation\Http\FormRequest;

class SuggestBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'             => ['required', 'date_format:Y-m-d'],
            'duration_minutes' => ['required', 'integer', 'in:' . implode(',', BookingPeriod::VALID_DURATIONS)],
            'preferred_start'  => ['sometimes', 'integer', 'min:' . BookingPeriod::DAY_START, 'max:' . BookingPeriod::DAY_END],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'             => 'O campo date é obrigatório.',
            'date.date_format'          => 'A data deve estar no formato YYYY-MM-DD.',
            'duration_minutes.required' => 'O campo duration_minutes é obrigatório.',
            'duration_minutes.in'       => 'A duração deve ser 30, 60, 90 ou 120 minutos.',
            'preferred_start.integer'   => 'preferred_start deve ser um inteiro (minutos desde meia-noite).',
            'preferred_start.min'       => 'preferred_start deve ser a partir das 07:00 (420).',
            'preferred_start.max'       => 'preferred_start deve ser até 20:00 (1200).',
        ];
    }
}
