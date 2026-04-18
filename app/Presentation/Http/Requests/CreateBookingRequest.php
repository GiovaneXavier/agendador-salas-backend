<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests;

use App\Domain\Booking\ValueObjects\BookingPeriod;
use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id'          => ['required', 'string', 'uuid'],
            'date'             => ['required', 'date_format:Y-m-d'],
            'start_minute'     => ['required', 'integer', 'min:' . BookingPeriod::DAY_START, 'max:' . (BookingPeriod::DAY_END - 30)],
            'duration_minutes' => ['required', 'integer', 'in:' . implode(',', BookingPeriod::VALID_DURATIONS)],
            'username'         => ['required', 'string', 'min:3', 'max:100', 'regex:/^[a-zA-Z0-9._\-]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required'          => 'O campo room_id é obrigatório.',
            'room_id.uuid'              => 'O room_id deve ser um UUID válido.',
            'date.required'             => 'O campo date é obrigatório.',
            'date.date_format'          => 'A data deve estar no formato YYYY-MM-DD.',
            'start_minute.required'     => 'O campo start_minute é obrigatório.',
            'start_minute.integer'      => 'start_minute deve ser um número inteiro.',
            'start_minute.min'          => 'O horário de início deve ser a partir das 07:00 (420 minutos).',
            'start_minute.max'          => 'O horário de início deve permitir ao menos 30 minutos antes das 20:00.',
            'duration_minutes.required' => 'O campo duration_minutes é obrigatório.',
            'duration_minutes.in'       => 'A duração deve ser 30, 60, 90 ou 120 minutos.',
            'username.required'         => 'O campo username é obrigatório.',
            'username.min'              => 'O username deve ter ao menos 3 caracteres.',
            'username.regex'            => 'O username só pode conter letras, números, pontos, hífens e underscores.',
        ];
    }
}
