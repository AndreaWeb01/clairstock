<?php

namespace App\Http\Requests;

use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sale = $this->route('sale');
        if ($sale instanceof Sale) return $this->user()->can('addPayment', $sale);
        $purchase = $this->route('purchase');
        if ($purchase instanceof Purchase) return $this->user()->can('addPayment', $purchase);
        return false;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999'],
            'method' => ['required', 'in:cash,card,transfer,mobile_money,other'],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Le montant du paiement est obligatoire.',
            'amount.gt' => 'Le paiement doit être supérieur à zéro.',
            'method.required' => 'Sélectionnez un moyen de paiement.',
            'paid_at.before_or_equal' => 'La date du paiement ne peut pas être dans le futur.',
        ];
    }
}
