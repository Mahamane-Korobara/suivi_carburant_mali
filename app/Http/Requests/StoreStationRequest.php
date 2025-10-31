<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Tout le monde peut faire une demande d'inscription
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
            // Le champ 'fuel_types' est maintenant un tableau d’IDs
            'fuel_types' => 'required|array|min:1',
            'fuel_types.*' => 'exists:fuel_types,id',
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
            'fuel_types.required' => 'Vous devez sélectionner au moins un type de carburant.',
            'fuel_types.*.exists' => 'Un type de carburant sélectionné est invalide.',
        ];
    }
}
