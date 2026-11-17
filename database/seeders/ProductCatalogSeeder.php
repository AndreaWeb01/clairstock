<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->whereHas('role', fn ($query) => $query->where('name', Role::ADMIN))->firstOrFail();
        $categories = Category::query()->where('is_active', true)->get()->keyBy('name');
        $products = [
            ['Alimentation', 'Riz parfumé 5 kg', 'ALIM-RIZ-005', 'Sac de riz parfumé, format 5 kg.', 'paquet', 3500, 4200, 5, 30],
            ['Alimentation', 'Riz local 25 kg', 'ALIM-RIZ-025', 'Sac de riz local, format 25 kg.', 'paquet', 14500, 17500, 2, 12],
            ['Alimentation', 'Huile végétale 1 L', 'ALIM-HUI-001', 'Bouteille d’huile végétale.', 'litre', 950, 1200, 10, 48],
            ['Alimentation', 'Sucre en poudre 1 kg', 'ALIM-SUC-001', 'Paquet de sucre en poudre.', 'kg', 700, 900, 10, 40],
            ['Alimentation', 'Farine de blé 1 kg', 'ALIM-FAR-001', 'Paquet de farine de blé.', 'kg', 650, 850, 8, 35],
            ['Alimentation', 'Pâtes alimentaires 500 g', 'ALIM-PAT-500', 'Paquet de pâtes alimentaires.', 'paquet', 450, 600, 12, 50],
            ['Alimentation', 'Lait en poudre 400 g', 'ALIM-LAI-400', 'Boîte de lait en poudre.', 'boite', 1800, 2200, 6, 24],
            ['Alimentation', 'Haricots rouges 1 kg', 'ALIM-HAR-001', 'Haricots rouges secs en paquet.', 'kg', 900, 1200, 5, 20],
            ['Boissons', 'Eau minérale 1,5 L', 'BOIS-EAU-015', 'Bouteille d’eau minérale.', 'litre', 250, 350, 24, 96],
            ['Boissons', 'Jus d’orange 1 L', 'BOIS-JUS-001', 'Brique de jus d’orange.', 'litre', 800, 1100, 8, 30],
            ['Boissons', 'Soda bouteille 1,5 L', 'BOIS-SOD-015', 'Bouteille de boisson gazeuse.', 'litre', 700, 1000, 12, 48],
            ['Hygiène', 'Savon de toilette', 'HYGI-SAV-001', 'Savon de toilette à l’unité.', 'piece', 250, 400, 20, 80],
            ['Hygiène', 'Dentifrice 75 ml', 'HYGI-DEN-075', 'Tube de dentifrice.', 'piece', 600, 850, 8, 30],
            ['Hygiène', 'Papier hygiénique 4 rouleaux', 'HYGI-PAP-004', 'Paquet de quatre rouleaux.', 'paquet', 900, 1200, 8, 32],
            ['Entretien', 'Eau de javel 1 L', 'ENTR-JAV-001', 'Bouteille d’eau de javel.', 'litre', 500, 700, 8, 30],
            ['Entretien', 'Liquide vaisselle 750 ml', 'ENTR-VAIS-750', 'Bouteille de liquide vaisselle.', 'piece', 750, 1000, 6, 24],
            ['Épicerie', 'Café moulu 250 g', 'EPIC-CAF-250', 'Paquet de café moulu.', 'paquet', 1500, 1900, 5, 20],
            ['Épicerie', 'Tomate concentrée 400 g', 'EPIC-TOM-400', 'Boîte de concentré de tomate.', 'boite', 500, 700, 12, 48],
            ['Fournitures', 'Cahier 200 pages', 'FOUR-CAH-200', 'Cahier scolaire de 200 pages.', 'piece', 800, 1100, 10, 40],
            ['Maison', 'Seau plastique 15 L', 'MAIS-SEA-015', 'Seau plastique avec anse.', 'piece', 1800, 2400, 4, 16],
        ];

        foreach ($products as [$categoryName, $name, $sku, $description, $unit, $cost, $price, $minimum, $stock]) {
            $category = $categories->get($categoryName);
            if (! $category) {
                $this->command?->warn("Catégorie absente, produit ignoré : {$name} ({$categoryName}).");
                continue;
            }

            $product = Product::query()->updateOrCreate(['sku' => $sku], [
                'category_id' => $category->id,
                'name' => $name,
                'description' => $description,
                'unit' => $unit,
                'purchase_price' => $cost,
                'sale_price' => $price,
                'minimum_stock' => $minimum,
                'status' => 'active',
            ]);

            if ((float) $product->stock_quantity === 0.0) {
                app(StockService::class)->record($product, 'entry', $stock, $admin, 'Stock initial de démonstration');
            }
        }

        $this->command?->info('Catalogue de démonstration créé : 20 produits avec catégories et stocks initiaux.');
    }
}
