<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CatalogueAndStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, CategorySeeder::class]);
    }

    public function test_administrator_can_create_a_category_and_employees_can_only_view_it(): void
    {
        $admin = $this->userWithRole(Role::ADMIN);
        $employee = $this->userWithRole(Role::EMPLOYEE);

        $this->actingAs($admin)->post(route('categories.store'), [
            'name' => 'Équipement', 'description' => 'Matériel du commerce', 'is_active' => 1,
        ])->assertRedirect(route('categories.index'));

        $category = Category::where('name', 'Équipement')->firstOrFail();
        $this->actingAs($employee)->get(route('categories.index'))->assertOk();
        $this->actingAs($employee)->get(route('categories.edit', $category))->assertForbidden();
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $admin = $this->userWithRole(Role::ADMIN);
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();

        $this->actingAs($admin)->delete(route('categories.destroy', $category))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_sku_must_be_unique_and_employees_cannot_create_products(): void
    {
        $admin = $this->userWithRole(Role::ADMIN);
        $employee = $this->userWithRole(Role::EMPLOYEE);
        $category = Category::factory()->create();
        Product::factory()->for($category)->create(['sku' => 'SKU-UNIQUE-1']);
        $data = [
            'category_id' => $category->id,
            'name' => 'Savon de test',
            'sku' => 'SKU-UNIQUE-1',
            'unit' => 'piece',
            'purchase_price' => '500.00',
            'sale_price' => '750.00',
            'minimum_stock' => '3.000',
            'status' => 'active',
        ];

        $this->actingAs($admin)->post(route('products.store'), $data)->assertSessionHasErrors('sku');
        $this->actingAs($employee)->post(route('products.store'), [...$data, 'sku' => 'SKU-EMPLOYEE-1'])->assertForbidden();
    }

    public function test_stock_movements_record_balances_and_reject_negative_stock(): void
    {
        $user = $this->userWithRole(Role::EMPLOYEE);
        $product = Product::factory()->create(['stock_quantity' => '0.000']);
        $stock = app(StockService::class);

        $entry = $stock->record($product, 'entry', '6.500', $user, 'Réception initiale');
        $this->assertSame('0.000', $entry->stock_before);
        $this->assertSame('6.500', $entry->stock_after);

        $exit = $stock->record($product, 'exit', '1.250', $user, 'Sortie inventaire');
        $this->assertSame('5.250', $exit->stock_after);
        $this->assertSame('5.250', $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('stock_movements', 2);

        try {
            $stock->record($product, 'exit', '5.251', $user, 'Sortie trop importante');
            $this->fail('Une sortie supérieure au stock doit être refusée.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quantity', $exception->errors());
        }

        $this->assertSame('5.250', $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_stock_adjustments_are_logged_and_low_stock_is_classified(): void
    {
        $user = $this->userWithRole(Role::ADMIN);
        $product = Product::factory()->create(['stock_quantity' => '0.000', 'minimum_stock' => '2.000']);
        app(StockService::class)->record($product, 'adjustment_positive', '2.000', $user, 'Inventaire');

        $this->assertSame('low', $product->fresh()->stockState());
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'adjustment_positive',
            'stock_before' => '0.000',
            'stock_after' => '2.000',
        ]);
    }

    private function userWithRole(string $roleName): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $roleName)->value('id'),
        ]);
    }
}
