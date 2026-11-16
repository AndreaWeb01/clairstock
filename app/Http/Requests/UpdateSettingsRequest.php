<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->isAdmin(); }

    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:180'],
            'business_phone' => ['nullable', 'string', 'max:40'],
            'business_email' => ['nullable', 'email', 'max:255'],
            'business_address' => ['nullable', 'string', 'max:2000'],
            'business_country' => ['nullable', 'string', 'max:120'],
            'currency' => ['required', 'in:FCFA,EUR,USD,GNF'],
            'date_format' => ['required', 'in:d/m/Y,Y-m-d,m/d/Y'],
            'default_minimum_stock' => ['required', 'numeric', 'min:0', 'decimal:0,3', 'max:999999999'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];
    }

    public function messages(): array
    {
        return [
            'business_name.required' => 'Le nom du commerce est obligatoire.',
            'business_email.email' => 'L’adresse e-mail doit être valide.',
            'currency.in' => 'Sélectionnez une devise proposée.',
            'default_minimum_stock.decimal' => 'Le seuil par défaut accepte au maximum trois décimales.',
            'logo.image' => 'Le logo doit être une image.',
            'logo.max' => 'Le logo ne doit pas dépasser 3 Mo.',
        ];
    }
}
