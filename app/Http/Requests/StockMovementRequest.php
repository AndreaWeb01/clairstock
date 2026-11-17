<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\StockMovement::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', Rule::exists('products', 'id')->where('status', 'active')],
            'movement_type' => ['required', Rule::in(['entry', 'exit', 'adjustment_positive', 'adjustment_negative'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:999999999'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Veuillez choisir un produit.',
            'product_id.exists' => 'Le produit sélectionné est indisponible.',
            'movement_type.required' => 'Veuillez choisir le type de mouvement.',
            'movement_type.in' => 'Le type de mouvement choisi est invalide.',
            'quantity.required' => 'La quantité est obligatoire.',
            'quantity.integer' => 'La quantité doit être un nombre entier.',
            'quantity.min' => 'La quantité doit être supérieure à zéro.',
            'reason.required' => 'Veuillez indiquer le motif du mouvement.',
            'reason.min' => 'Le motif doit contenir au moins 3 caractères.',
            'reason.max' => 'Le motif ne peut pas dépasser 500 caractères.',
        ];
    }
}
