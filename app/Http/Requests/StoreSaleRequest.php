<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('create', \App\Models\Sale::class); }
    public function rules(): array { return ['customer_id' => ['nullable','integer','exists:customers,id'], 'sold_at' => ['required','date'], 'discount_amount' => ['nullable','numeric','min:0','decimal:0,2','max:9999999999999'], 'amount_paid' => ['nullable','numeric','min:0','decimal:0,2','max:9999999999999'], 'payment_method' => ['nullable','in:cash,card,transfer,mobile_money,other'], 'notes' => ['nullable','string','max:5000'], 'items' => ['required','array','min:1','max:100'], 'items.*.product_id' => ['required','integer','distinct','exists:products,id'], 'items.*.quantity' => ['required','numeric','gt:0','decimal:0,3','max:999999999']]; }
    public function messages(): array { return ['items.required' => 'Ajoutez au moins un produit à la vente.', 'items.*.product_id.required' => 'Sélectionnez un produit pour chaque ligne.', 'items.*.product_id.distinct' => 'Un produit ne peut apparaître qu’une seule fois dans la vente.', 'items.*.quantity.gt' => 'La quantité doit être supérieure à zéro.', 'amount_paid.numeric' => 'Le montant payé doit être un nombre valide.']; }
}
