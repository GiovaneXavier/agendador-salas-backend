<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListBookingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'string', 'uuid'],
            'date'    => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'O campo room_id é obrigatório.',
            'room_id.uuid'     => 'O room_id deve ser um UUID válido.',
            'date.required'    => 'O campo date é obrigatório.',
            'date.date_format' => 'A data deve estar no formato YYYY-MM-DD.',
        ];
    }
}
