<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use App\Services\SaleService;
use App\Services\StockService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SalesAndPurchasesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, CategorySeeder::class]);
    }

    public function test_draft_purchase_does_not_change_stock_and_receipt_adds_stock_and_movement(): void
    {
        [$user, $product] = $this->userAndProduct();
        $supplier = Supplier::create(['name' => 'Fournisseur test']);
        $purchase = app(PurchaseService::class)->create([
            'supplier_id' => $supplier->id, 'purchased_at' => today()->toDateString(), 'status' => 'draft',
            'items' => [['product_id' => $product->id, 'quantity' => '4.000', 'unit_cost' => '500.00']],
        ], $user);

        $this->assertSame('draft', $purchase->status);
        $this->assertSame('2000.00', $purchase->subtotal);
        $this->assertSame('0.000', $product->fresh()->stock_quantity);
        $purchase = app(PurchaseService::class)->receive($purchase, $user);

        $this->assertSame('received', $purchase->status);
        $this->assertSame('4.000', $product->fresh()->stock_quantity);
        $this->assertSame('500.00', $product->fresh()->purchase_price);
        $this->assertDatabaseHas('stock_movements', ['movement_type' => 'purchase', 'stock_after' => '4.000']);
    }

    public function test_sale_calculates_totals_decreases_stock_and_tracks_initial_credit(): void
    {
        [$user, $product] = $this->userAndProduct();
        app(StockService::class)->record($product, 'entry', '10.000', $user, 'Stock de test');
        $customer = Customer::create(['name' => 'Cliente test']);
        $sale = app(SaleService::class)->create([
            'customer_id' => $customer->id, 'sold_at' => today()->toDateString(), 'discount_amount' => '100.00', 'amount_paid' => '900.00',
            'items' => [['product_id' => $product->id, 'quantity' => '2.000']],
        ], $user);

        $this->assertSame('1900.00', $sale->total_amount);
        $this->assertSame('1000.00', $sale->amount_due);
        $this->assertSame('partial', $sale->payment_status);
        $this->assertSame('8.000', $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', ['movement_type' => 'sale', 'stock_after' => '8.000']);
    }

    public function test_sale_with_insufficient_stock_rolls_back_sale_items_and_movements(): void
    {
        [$user, $product] = $this->userAndProduct();

        try {
            app(SaleService::class)->create([
                'sold_at' => today()->toDateString(), 'items' => [['product_id' => $product->id, 'quantity' => '1.000']],
            ], $user);
            $this->fail('La vente doit être refusée lorsque le stock est insuffisant.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('sales', 0);
            $this->assertDatabaseCount('sale_items', 0);
            $this->assertDatabaseCount('stock_movements', 0);
            $this->assertSame('0.000', $product->fresh()->stock_quantity);
        }
    }

    public function test_received_purchase_cannot_be_cancelled_when_items_are_no_longer_available(): void
    {
        [$user, $product] = $this->userAndProduct();
        $supplier = Supplier::create(['name' => 'Fournisseur test']);
        $purchase = app(PurchaseService::class)->create([
            'supplier_id' => $supplier->id, 'purchased_at' => today()->toDateString(), 'status' => 'received',
            'items' => [['product_id' => $product->id, 'quantity' => '2.000', 'unit_cost' => '500.00']],
        ], $user);
        app(\App\Services\StockService::class)->record($product, 'sale', '1.000', $user, 'Vente indépendante');

        try {
            app(PurchaseService::class)->cancel($purchase, $user);
            $this->fail('L’annulation doit échouer si les articles sont déjà sortis du stock.');
        } catch (ValidationException) {
            $this->assertSame('received', $purchase->fresh()->status);
            $this->assertSame('1.000', $product->fresh()->stock_quantity);
        }
    }

    private function userAndProduct(): array
    {
        $user = User::factory()->create(['role_id' => Role::where('name', Role::ADMIN)->value('id')]);
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create(['purchase_price' => '500.00', 'sale_price' => '1000.00', 'stock_quantity' => '0.000']);
        return [$user, $product];
    }
}
