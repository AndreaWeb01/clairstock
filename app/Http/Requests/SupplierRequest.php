<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can($this->route('supplier') ? 'update' : 'create', $this->route('supplier') ?? \App\Models\Supplier::class); }
    public function rules(): array { return ['name' => ['required','string','max:150'], 'company' => ['nullable','string','max:180'], 'phone' => ['nullable','string','max:40'], 'email' => ['nullable','email','max:255'], 'address' => ['nullable','string','max:2000'], 'city' => ['nullable','string','max:120'], 'country' => ['nullable','string','max:120'], 'notes' => ['nullable','string','max:5000']]; }
    public function messages(): array { return ['name.required' => 'Le nom du fournisseur est obligatoire.', 'email.email' => 'L’adresse e-mail doit être valide.']; }
}
