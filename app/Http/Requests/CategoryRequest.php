<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category
            ? ($this->user()?->can('update', $category) ?? false)
            : ($this->user()?->can('create', Category::class) ?? false);
    }

    public function rules(): array
    {
        $category = $this->route('category');
        $uniqueName = Rule::unique('categories', 'name');
        if ($category) {
            $uniqueName->ignore($category->id);
        }

        return [
            'name' => ['required', 'string', 'max:120', $uniqueName],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.unique' => 'Une catégorie porte déjà ce nom.',
            'name.max' => 'Le nom ne peut pas dépasser 120 caractères.',
            'description.max' => 'La description est trop longue.',
            'is_active.required' => 'Veuillez choisir un statut.',
            'is_active.boolean' => 'Le statut choisi est invalide.',
        ];
    }
}
