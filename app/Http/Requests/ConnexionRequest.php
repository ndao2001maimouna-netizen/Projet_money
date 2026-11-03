<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConnexionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('telephone'))) {
            $this->merge([
                'telephone' => preg_replace('/\s+/', '', $this->input('telephone')),
            ]);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'telephone' => ['required', 'regex:/^(70|75|76|77|78)[0-9]{7}$/'],
            'code_secret' => ['required', 'digits:4'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'telephone.required' => 'Le téléphone est obligatoire pour se connecter.',
            'telephone.regex' => 'Entrez un numéro  valide de 9 chiffres.',
            'code_secret.required' => 'Le code secret est obligatoire.',
            'code_secret.digits' => 'Votre code doit contenir 4 chiffres.',
        ];
    }
}
