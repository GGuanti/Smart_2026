<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Cambia in base alla tua logica di autorizzazione
    }

    public function rules(): array
    {
                return [
                    'client_id' => 'required|exists:clients,id',
                    'title' => 'required|string|max:255',
                    'start_time' => 'required|date',
                    'end_time' => 'required|date|after_or_equal:start_time',
                    'status' => 'required|string|in:scheduled,completed,cancelled',
                    'description' => 'nullable|string',
                ];


    }
}
