<?php

namespace App\Http\Requests;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $expense = $this->route('expense');
        return $this->user()->can($expense ? 'update' : 'create', $expense ?? Expense::class);
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'description' => ['required', 'string', 'max:200'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999'],
            'spent_at' => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['nullable', 'in:cash,card,transfer,mobile_money,other'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'expense_category_id.required' => 'Sélectionnez une catégorie de dépense.',
            'description.required' => 'La description est obligatoire.',
            'amount.gt' => 'Le montant doit être supérieur à zéro.',
            'spent_at.before_or_equal' => 'La date ne peut pas être dans le futur.',
            'receipt.mimes' => 'Le justificatif doit être une image JPG/PNG ou un fichier PDF.',
            'receipt.max' => 'Le justificatif ne doit pas dépasser 5 Mo.',
        ];
    }
}
