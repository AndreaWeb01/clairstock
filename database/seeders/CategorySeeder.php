<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Alimentation' => 'Produits alimentaires et denrées.',
            'Boissons' => 'Boissons et eaux.',
            'Hygiène' => 'Produits d’hygiène et de soin.',
            'Entretien' => 'Produits et accessoires d’entretien.',
            'Fournitures' => 'Fournitures de bureau et scolaires.',
            'Épicerie' => 'Produits d’épicerie courante.',
            'Électronique' => 'Appareils et accessoires électroniques.',
            'Maison' => 'Articles pour la maison.',
            'Accessoires' => 'Accessoires divers.',
            'Divers' => 'Articles ne relevant pas des autres familles.',
        ];

        foreach ($categories as $name => $description) {
            Category::firstOrCreate(['name' => $name], ['description' => $description, 'is_active' => true]);
        }
    }
}
