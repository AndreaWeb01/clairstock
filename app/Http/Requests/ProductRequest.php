<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product
            ? ($this->user()?->can('update', $product) ?? false)
            : ($this->user()?->can('create', Product::class) ?? false);
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $uniqueSku = Rule::unique('products', 'sku');
        if ($product) {
            $uniqueSku->ignore($product->id);
        }

        return [
            'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:180'],
            'sku' => ['required', 'string', 'max:80', $uniqueSku],
            'description' => ['nullable', 'string', 'max:10000'],
            'unit' => ['required', Rule::in(Product::UNITS)],
            'purchase_price' => ['required', 'numeric', 'gt:0', 'lte:9999999999999.99', 'decimal:0,2'],
            'sale_price' => ['required', 'numeric', 'gt:0', 'lte:9999999999999.99', 'decimal:0,2'],
            'minimum_stock' => ['required', 'integer', 'min:0', 'max:999999999'],
            'status' => ['required', Rule::in(Product::STATUSES)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Veuillez choisir une catégorie.',
            'category_id.exists' => 'La catégorie est indisponible.',
            'name.required' => 'Le nom du produit est obligatoire.',
            'name.max' => 'Le nom ne peut pas dépasser 180 caractères.',
            'sku.required' => 'La référence SKU est obligatoire.',
            'sku.unique' => 'Cette référence SKU est déjà utilisée.',
            'description.max' => 'La description est trop longue.',
            'unit.required' => 'Veuillez choisir une unité.',
            'unit.in' => 'L’unité choisie est invalide.',
            'purchase_price.required' => 'Le prix d’achat est obligatoire.',
            'purchase_price.numeric' => 'Le prix d’achat doit être un montant valide.',
            'purchase_price.gt' => 'Le prix d’achat doit être supérieur à zéro.',
            'purchase_price.lte' => 'Le prix d’achat dépasse le montant maximal autorisé.',
            'purchase_price.decimal' => 'Le prix d’achat peut comporter au maximum deux décimales.',
            'sale_price.required' => 'Le prix de vente est obligatoire.',
            'sale_price.numeric' => 'Le prix de vente doit être un montant valide.',
            'sale_price.gt' => 'Le prix de vente doit être supérieur à zéro.',
            'sale_price.lte' => 'Le prix de vente dépasse le montant maximal autorisé.',
            'sale_price.decimal' => 'Le prix de vente peut comporter au maximum deux décimales.',
            'minimum_stock.required' => 'Le seuil minimal est obligatoire.',
            'minimum_stock.numeric' => 'Le seuil minimal doit être un nombre valide.',
            'minimum_stock.min' => 'Le seuil minimal ne peut pas être négatif.',
            'minimum_stock.lte' => 'Le seuil dépasse la quantité maximale autorisée.',
            'minimum_stock.integer' => 'Le seuil doit être un nombre entier.',
            'status.required' => 'Veuillez choisir un statut.',
            'status.in' => 'Le statut choisi est invalide.',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'L’image doit être au format JPG, PNG ou WebP.',
            'image.max' => 'L’image ne peut pas dépasser 2 Mo.',
        ];
    }
}
