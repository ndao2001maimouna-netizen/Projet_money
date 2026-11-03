<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InscriptionRequest extends FormRequest
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
            'nom' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\p{M}]+(?:[ \'\x{2019}-][\p{L}\p{M}]+)*$/u'],
            'prenom' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\p{M}]+(?:[ \'\x{2019}-][\p{L}\p{M}]+)*$/u'],
            'telephone' => ['required', 'regex:/^(70|75|76|77|78)[0-9]{7}$/', 'unique:utilisateurs,telephone'],
            'code_secret' => ['required', 'digits:4','min:4','max:20'],
            'code_secret_confirmation' => ['required', 'digits:4', 'min:4','max:20', 'same:code_secret'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le  nom est obligatoire.',
            'nom.regex' => 'Le nom ne peut contenir que des lettres, espaces.',
            'prenom.required' => 'Le  prénom est obligatoire.',
            'prenom.regex' => 'Le prénom ne peut contenir que des lettres, espaces.',
            'telephone.required' => 'Le téléphone est obligatoire.',
            'telephone.regex' => 'Entrez un numéro valide de 9 chiffres.',
            'telephone.unique' => 'Le numéro doit être unique.',
            'code_secret.required' => 'Veuillez entrer votre code secret.',
            'code_secret.digits' => 'Votre code doit contenir minimaux 4 maximun 20 chiffres.',
            'code_secret.min'=> 'Votre code doit contenir minimaux 4 ',
            'code_secret.max'=> 'Votre code doit contenir  maximun 20 chiffres',
            'code_secret_confirmation.required' => 'Veuillez confirmer votre code secret.',
            'code_secret_confirmation.digits' => 'Votre code doit contenir minimaux 4 maximun 20 chiffres.',
           'code_secret_confirmation.min' => 'Votre code doit contenir minimaux 4 ',
           'code_secret_confirmation.max' => 'Votre code doit contenir  maximun 20 chiffres.',
            'code_secret_confirmation.same' => 'Les deux codes secrets doivent être identiques.',
        ];
    }
}
