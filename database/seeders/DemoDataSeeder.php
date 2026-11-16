<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use App\Services\SaleService;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! App::environment(['local', 'testing'])) {
            throw new \RuntimeException('Les données de démonstration sont réservées aux environnements local et testing.');
        }

        if (Product::query()->exists() || User::query()->whereHas('role', fn ($query) => $query->where('name', Role::ADMIN))->doesntExist()) {
            throw new \RuntimeException('Créez d’abord le compte administrateur et lancez le seeder de démonstration sur une base sans produits.');
        }

        $administrator = User::query()->whereHas('role', fn ($query) => $query->where('name', Role::ADMIN))->firstOrFail();
        $employeeRoleId = Role::query()->where('name', Role::EMPLOYEE)->value('id');
        $employees = collect(range(1, 2))->map(function () use ($employeeRoleId) {
            $password = Str::random(32);
            $employee = User::factory()->create(['role_id' => $employeeRoleId, 'password' => $password]);
            $this->command?->line("Compte employé de démonstration : {$employee->email} — mot de passe temporaire : {$password}");
            return $employee;
        });
        $actors = $employees->push($administrator)->values();

        $categories = Category::query()->where('is_active', true)->get();
        if ($categories->count() < 10) {
            $categories = $categories->concat(Category::factory()->count(10 - $categories->count())->create())->values();
        }
        $products = Product::factory()->count(50)->state(fn () => [
            'category_id' => $categories->random()->id,
        ])->create();
        $suppliers = Supplier::factory()->count(10)->create();
        $customers = Customer::factory()->count(30)->create();
        $stock = app(StockService::class);
        $purchases = app(PurchaseService::class);

        foreach ($suppliers as $index => $supplier) {
            $items = $products->slice($index * 5, 5)->map(fn (Product $product) => [
                'product_id' => $product->id, 'quantity' => '20.000', 'unit_cost' => $product->purchase_price,
            ])->values()->all();
            $purchases->create([
                'supplier_id' => $supplier->id,
                'purchased_at' => now()->subDays(random_int(1, 25))->toDateString(),
                'status' => 'received', 'items' => $items,
            ], $actors[$index % $actors->count()]);
        }

        foreach ($products as $index => $product) {
            $product->refresh();
            if ($index === 0) {
                $stock->record($product, 'adjustment_negative', $product->stock_quantity, $administrator, 'Produit de démonstration en rupture');
            } elseif ($index === 1) {
                $product->update(['minimum_stock' => '5.000']);
                $stock->record($product, 'adjustment_negative', (float) $product->stock_quantity - 2, $administrator, 'Produit de démonstration en stock faible');
            }
        }

        $sales = app(SaleService::class);
        foreach (range(1, 25) as $index) {
            $product = $products->slice(2)->random();
            $quantity = random_int(1, 2);
            if ((float) $product->fresh()->stock_quantity < $quantity) continue;
            $total = round((float) $product->sale_price * $quantity, 2);
            $sales->create([
                'customer_id' => $index % 5 === 0 ? null : $customers->random()->id,
                'sold_at' => now()->subDays(random_int(0, 27))->toDateString(),
                'payment_method' => fake()->randomElement(['cash', 'card', 'mobile_money', 'transfer']),
                'amount_paid' => $index % 4 === 0 ? '0.00' : number_format($total / 2, 2, '.', ''),
                'items' => [['product_id' => $product->id, 'quantity' => number_format($quantity, 3, '.', '')]],
            ], $actors[$index % $actors->count()]);
        }

        $expenseCategories = ExpenseCategory::query()->where('is_active', true)->get();
        foreach ($expenseCategories as $index => $category) {
            Expense::create([
                'expense_category_id' => $category->id, 'user_id' => $administrator->id,
                'description' => 'Dépense de démonstration — '.$category->name,
                'amount' => fake()->randomFloat(2, 5000, 125000),
                'spent_at' => now()->subDays($index)->toDateString(),
                'payment_method' => fake()->randomElement(['cash', 'transfer', 'mobile_money']),
            ]);
        }
    }
}
