<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can($this->route('purchase') ? 'update' : 'create', $this->route('purchase') ?? \App\Models\Purchase::class); }
    public function rules(): array { return ['supplier_id' => ['required','integer','exists:suppliers,id'], 'purchased_at' => ['required','date'], 'status' => ['required','in:draft,received'], 'discount_amount' => ['nullable','numeric','min:0','decimal:0,2','max:9999999999999'], 'notes' => ['nullable','string','max:5000'], 'items' => ['required','array','min:1','max:100'], 'items.*.product_id' => ['required','integer','distinct','exists:products,id'], 'items.*.quantity' => ['required','numeric','gt:0','decimal:0,3','max:999999999'], 'items.*.unit_cost' => ['required','numeric','min:0','decimal:0,2','max:9999999999999']]; }
    public function messages(): array { return ['items.required' => 'Ajoutez au moins un produit à l’achat.', 'items.*.product_id.required' => 'Sélectionnez un produit pour chaque ligne.', 'items.*.product_id.distinct' => 'Un produit ne peut apparaître qu’une seule fois dans l’achat.', 'items.*.quantity.gt' => 'La quantité doit être supérieure à zéro.', 'items.*.unit_cost.required' => 'Le prix d’achat unitaire est obligatoire.']; }
}
