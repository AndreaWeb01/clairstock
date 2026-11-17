<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use App\Services\SaleService;
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

        if (User::query()->whereHas('role', fn ($query) => $query->where('name', Role::ADMIN))->doesntExist()) {
            throw new \RuntimeException('Créez d’abord le compte administrateur avant de lancer le seeder de démonstration.');
        }

        $administrator = User::query()->whereHas('role', fn ($query) => $query->where('name', Role::ADMIN))->firstOrFail();
        $employeeRoleId = Role::query()->where('name', Role::EMPLOYEE)->value('id');
        $employees = User::query()->where('role_id', $employeeRoleId)->get();
        while ($employees->count() < 2) {
            $password = Str::random(32);
            $employee = User::factory()->create(['role_id' => $employeeRoleId, 'password' => $password]);
            $this->command?->line("Compte employé de démonstration : {$employee->email} — mot de passe temporaire : {$password}");
            $employees->push($employee);
        }
        $actors = $employees->push($administrator)->values();

        $categories = Category::query()->where('is_active', true)->get();
        if ($categories->count() < 10) {
            $categories = $categories->concat(Category::factory()->count(10 - $categories->count())->create())->values();
        }
        $products = Product::query()->get();
        if ($products->isEmpty()) {
            $products = Product::factory()->count(50)->state(fn () => [
                'category_id' => $categories->random()->id,
            ])->create();
        }
        if ($products->count() < 3) {
            throw new \RuntimeException('Le seeder a besoin d’au moins trois produits pour créer des achats et des ventes.');
        }
        $suppliers = Supplier::query()->get();
        if ($suppliers->isEmpty()) {
            $suppliers = Supplier::factory()->count(10)->create();
        }
        $customers = Customer::query()->get();
        if ($customers->isEmpty()) {
            $customers = Customer::factory()->count(30)->create();
        }
        $purchases = app(PurchaseService::class);

        if (Purchase::query()->doesntExist()) {
            foreach ($suppliers->take(10) as $index => $supplier) {
                $items = $products->shuffle()->take(min(5, $products->count()))->map(fn (Product $product) => [
                    'product_id' => $product->id, 'quantity' => '20', 'unit_cost' => $product->purchase_price,
                ])->values()->all();
                $purchases->create([
                    'supplier_id' => $supplier->id,
                    'purchased_at' => now()->subDays(random_int(1, 25))->toDateString(),
                    'status' => 'received', 'items' => $items,
                ], $actors[$index % $actors->count()]);
            }
        }

        $sales = app(SaleService::class);
        $salesToCreate = max(0, 25 - Sale::query()->count());
        for ($index = 0; $index < $salesToCreate; $index++) {
            $product = $products->slice(2)->random();
            $quantity = random_int(1, 2);
            if ((float) $product->fresh()->stock_quantity < $quantity) continue;
            $total = round((float) $product->sale_price * $quantity, 2);
            $sales->create([
                'customer_id' => $index % 5 === 0 ? null : $customers->random()->id,
                'sold_at' => now()->subDays(random_int(0, 27))->toDateString(),
                'payment_method' => fake()->randomElement(['cash', 'card', 'mobile_money', 'transfer']),
                'amount_paid' => $index % 4 === 0 ? '0.00' : number_format($total / 2, 2, '.', ''),
                'items' => [['product_id' => $product->id, 'quantity' => (string) $quantity]],
            ], $actors[$index % $actors->count()]);
        }

        if (Expense::query()->doesntExist()) {
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
}
