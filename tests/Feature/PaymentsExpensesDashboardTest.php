<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\PurchaseService;
use App\Services\SaleService;
use App\Services\DashboardService;
use App\Services\StockService;
use Carbon\CarbonImmutable;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentsExpensesDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, CategorySeeder::class, ExpenseCategorySeeder::class]);
    }

    public function test_multiple_sale_payments_update_the_balance_and_cannot_exceed_the_total(): void
    {
        [$admin, $product] = $this->userAndProduct();
        app(StockService::class)->record($product, 'entry', '10.000', $admin, 'Stock de test');
        $sale = app(SaleService::class)->create([
            'sold_at' => today()->toDateString(), 'amount_paid' => '500.00',
            'payment_method' => 'cash', 'items' => [['product_id' => $product->id, 'quantity' => '2.000']],
        ], $admin);
        $this->assertDatabaseCount('payments', 1);

        $payments = app(PaymentService::class);
        $payments->record($sale, ['amount' => '500.00', 'method' => 'mobile_money', 'paid_at' => today()->toDateString()], $admin);
        $this->assertSame('1000.00', $sale->fresh()->amount_due);
        $payments->record($sale, ['amount' => '1000.00', 'method' => 'cash', 'paid_at' => today()->toDateString()], $admin);
        $this->assertSame('0.00', $sale->fresh()->amount_due);
        $this->assertSame('paid', $sale->fresh()->payment_status);

        try {
            $payments->record($sale, ['amount' => '1.00', 'method' => 'cash', 'paid_at' => today()->toDateString()], $admin);
            $this->fail('Un paiement supérieur au solde doit être refusé.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('payments', 3);
            $this->assertSame('0.00', $sale->fresh()->amount_due);
        }
    }

    public function test_purchase_payment_updates_supplier_balance(): void
    {
        [$admin, $product] = $this->userAndProduct();
        $purchase = app(PurchaseService::class)->create([
            'supplier_id' => Supplier::create(['name' => 'Fournisseur test'])->id,
            'purchased_at' => today()->toDateString(), 'status' => 'received',
            'items' => [['product_id' => $product->id, 'quantity' => '2.000', 'unit_cost' => '500.00']],
        ], $admin);
        app(PaymentService::class)->record($purchase, ['amount' => '300.00', 'method' => 'transfer', 'paid_at' => today()->toDateString()], $admin);

        $this->assertSame('700.00', $purchase->fresh()->amount_due);
        $this->assertSame('partial', $purchase->fresh()->payment_status);
    }

    public function test_purchase_with_a_payment_cannot_be_cancelled_without_a_refund_flow(): void
    {
        [$admin, $product] = $this->userAndProduct();
        $purchase = app(PurchaseService::class)->create([
            'supplier_id' => Supplier::create(['name' => 'Fournisseur test'])->id,
            'purchased_at' => today()->toDateString(), 'status' => 'received',
            'items' => [['product_id' => $product->id, 'quantity' => '2.000', 'unit_cost' => '500.00']],
        ], $admin);
        app(PaymentService::class)->record($purchase, ['amount' => '100.00', 'method' => 'cash', 'paid_at' => today()->toDateString()], $admin);

        try {
            app(PurchaseService::class)->cancel($purchase, $admin);
            $this->fail('Un achat réglé ne doit pas être annulé sans remboursement/avoir.');
        } catch (\Illuminate\Validation\ValidationException) {
            $this->assertSame('received', $purchase->fresh()->status);
            $this->assertSame('100.00', $purchase->fresh()->amount_paid);
        }
    }

    public function test_admin_can_record_expense_and_employee_cannot_manage_expenses(): void
    {
        [$admin] = $this->userAndProduct();
        $employee = User::factory()->create(['role_id' => Role::where('name', Role::EMPLOYEE)->value('id')]);
        $category = ExpenseCategory::firstOrFail();
        $data = ['expense_category_id' => $category->id, 'description' => 'Transport marchandises', 'amount' => '2500.00', 'spent_at' => today()->toDateString(), 'payment_method' => 'cash'];

        $this->actingAs($admin)->post(route('expenses.store'), $data)->assertRedirect(route('expenses.index'));
        $this->assertDatabaseHas('expenses', ['description' => 'Transport marchandises', 'amount' => '2500.00']);
        $this->actingAs($employee)->get(route('expenses.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('expenses.create'))->assertForbidden();
    }

    public function test_dashboard_displays_real_stock_sales_and_expense_metrics(): void
    {
        [$admin, $product] = $this->userAndProduct();
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('Bénéfice estimé')->assertSee('Évolution des ventes');
    }

    public function test_dashboard_includes_sales_and_expenses_on_the_last_day_of_the_month(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-31 12:00:00'));
        try {
            [$admin, $product] = $this->userAndProduct();
            app(StockService::class)->record($product, 'entry', '5.000', $admin, 'Stock de test');
            app(SaleService::class)->create([
                'sold_at' => '2026-01-31', 'amount_paid' => '2000.00',
                'items' => [['product_id' => $product->id, 'quantity' => '2.000']],
            ], $admin);
            \App\Models\Expense::create([
                'expense_category_id' => ExpenseCategory::firstOrFail()->id,
                'user_id' => $admin->id, 'description' => 'Frais du dernier jour',
                'amount' => '100.00', 'spent_at' => '2026-01-31',
            ]);

            $dashboard = app(DashboardService::class)->data();
            $this->assertSame(1, $dashboard['monthSalesCount']);
            $this->assertEqualsWithDelta(2000.0, $dashboard['monthRevenue'], 0.01);
            $this->assertEqualsWithDelta(2000.0, $dashboard['salesByDay']->last()['total'], 0.01);
            $this->assertEqualsWithDelta(2000.0, (float) $dashboard['categorySales']->first()->total, 0.01);
            $this->assertEqualsWithDelta(900.0, $dashboard['estimatedProfit'], 0.01);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_dashboard_hides_administrator_financial_details_from_employees(): void
    {
        $this->assertNull(app(DashboardService::class)->data(false)['estimatedProfit']);
    }

    private function userAndProduct(): array
    {
        $user = User::factory()->create(['role_id' => Role::where('name', Role::ADMIN)->value('id')]);
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create(['purchase_price' => '500.00', 'sale_price' => '1000.00', 'stock_quantity' => '0.000']);
        return [$user, $product];
    }
}
