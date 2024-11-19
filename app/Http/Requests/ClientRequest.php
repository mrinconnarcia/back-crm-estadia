<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'contacto_emergencia' => 'nullable|string|max:20',
            'correo' => 'required|email|unique:clients,correo,' . $this->route('id'),
            'fecha_nacimiento' => 'required|date',
        ];
    }
}
