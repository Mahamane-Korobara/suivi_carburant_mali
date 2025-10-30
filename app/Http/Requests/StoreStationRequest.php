<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autoriser tout le monde à envoyer une demande d'inscription
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'quartier' => 'required|string|max:255',
            'commune' => 'required|string|max:255',
            'gerant_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|unique:stations,email',
            'type' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la station est obligatoire.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'email.required' => 'Le champ email est obligatoire.',
            'address.required' => 'Le champ adresse est obligatoire.',
            'quartier.required' => 'Le champ quartier est obligatoire.',
            'commune.required' => 'Le champ commune est obligatoire.',
            'gerant_name.required' => 'Le champ nom du gérant est obligatoire.',
            'phone.required' => 'Le champ téléphone est obligatoire.',
            'type.required' => 'Le type de station est obligatoire.',
        ];
    }
}
