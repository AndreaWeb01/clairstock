<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReportService
{
    public const PERIODS = ['today', 'yesterday', 'this_week', 'this_month', 'last_month', 'custom'];
    public const EXPORTS = ['sales', 'purchases', 'stock', 'expenses', 'movements'];

    public function resolvePeriod(string $period, ?string $from = null, ?string $to = null): array
    {
        $today = CarbonImmutable::today();
        return match ($period) {
            'today' => [$today, $today, 'Aujourd’hui'],
            'yesterday' => [$today->subDay(), $today->subDay(), 'Hier'],
            'this_week' => [$today->startOfWeek(CarbonImmutable::MONDAY), $today, 'Cette semaine'],
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth(), 'Mois précédent'],
            'custom' => [CarbonImmutable::parse($from), CarbonImmutable::parse($to), 'Période personnalisée'],
            default => [$today->startOfMonth(), $today, 'Ce mois'],
        };
    }

    public function data(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $range = [$from->toDateString(), $to->toDateString()];
        $salesQuery = Sale::query()->where('status', 'completed')->whereDate('sold_at', '>=', $range[0])->whereDate('sold_at', '<=', $range[1]);
        $purchasesQuery = Purchase::query()->where('status', 'received')->whereDate('purchased_at', '>=', $range[0])->whereDate('purchased_at', '<=', $range[1]);
        $expensesQuery = Expense::query()->where('is_active', true)->whereDate('spent_at', '>=', $range[0])->whereDate('spent_at', '<=', $range[1]);
        $movementsQuery = StockMovement::query()->whereBetween('occurred_at', [$from->startOfDay(), $to->endOfDay()]);
        $costs = DB::table('sale_items')->selectRaw('sale_id, SUM(quantity * unit_cost) as cost')->groupBy('sale_id');
        $salesMargin = (float) DB::table('sales')->leftJoinSub($costs, 'sale_costs', fn ($join) => $join->on('sales.id', '=', 'sale_costs.sale_id'))
            ->where('sales.status', 'completed')->whereDate('sales.sold_at', '>=', $range[0])->whereDate('sales.sold_at', '<=', $range[1])
            ->sum(DB::raw('sales.total_amount - COALESCE(sale_costs.cost, 0)'));

        $activeProducts = Product::query()->where('status', 'active');
        $stockValue = (float) (Product::query()->selectRaw('COALESCE(SUM(stock_quantity * purchase_price), 0) as value')->value('value') ?? 0);
        $categoryExpenses = (clone $expensesQuery)->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->selectRaw('expense_categories.name, COUNT(*) as count, SUM(expenses.amount) as total')
            ->groupBy('expense_categories.name')->orderByDesc('total')->get();
        $movementSummary = (clone $movementsQuery)->selectRaw('movement_type, COUNT(*) as count, SUM(quantity) as quantity')
            ->groupBy('movement_type')->orderBy('movement_type')->get();

        return [
            'salesSummary' => [
                'count' => (clone $salesQuery)->count(), 'revenue' => (float) (clone $salesQuery)->sum('total_amount'),
                'paid' => (float) (clone $salesQuery)->sum('amount_paid'), 'credit' => (float) (clone $salesQuery)->sum('amount_due'),
                'margin' => $salesMargin,
            ],
            'purchaseSummary' => [
                'count' => (clone $purchasesQuery)->count(), 'total' => (float) (clone $purchasesQuery)->sum('total_amount'),
                'paid' => (float) (clone $purchasesQuery)->sum('amount_paid'), 'due' => (float) (clone $purchasesQuery)->sum('amount_due'),
            ],
            'stockSummary' => [
                'products' => (clone $activeProducts)->count(),
                'quantity' => (float) (clone $activeProducts)->sum('stock_quantity'),
                'value' => $stockValue,
                'out' => (clone $activeProducts)->where('stock_quantity', '<=', 0)->count(),
                'low' => (clone $activeProducts)->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'minimum_stock')->count(),
            ],
            'expenseSummary' => ['total' => (float) (clone $expensesQuery)->sum('amount'), 'categories' => $categoryExpenses],
            'movementSummary' => $movementSummary,
            'salesRows' => (clone $salesQuery)->with(['customer', 'user'])->latest('sold_at')->paginate(10, ['*'], 'sales_page')->withQueryString(),
            'purchaseRows' => (clone $purchasesQuery)->with(['supplier', 'user'])->latest('purchased_at')->paginate(10, ['*'], 'purchases_page')->withQueryString(),
            'stockRows' => Product::query()->with('category')->orderBy('stock_quantity')->orderBy('name')->paginate(15, ['*'], 'stock_page')->withQueryString(),
            'expenseRows' => (clone $expensesQuery)->with(['category', 'user'])->latest('spent_at')->paginate(10, ['*'], 'expenses_page')->withQueryString(),
            'movementRows' => (clone $movementsQuery)->with(['product', 'user'])->latest('occurred_at')->paginate(15, ['*'], 'movements_page')->withQueryString(),
        ];
    }

    public function exportQuery(string $type, CarbonImmutable $from, CarbonImmutable $to): Builder|\Illuminate\Database\Eloquent\Builder
    {
        $range = [$from->toDateString(), $to->toDateString()];
        return match ($type) {
            'sales' => Sale::query()->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
                ->leftJoinSub(DB::table('sale_items')->selectRaw('sale_id, SUM(quantity * unit_cost) as cost')->groupBy('sale_id'), 'sale_costs', fn ($join) => $join->on('sales.id', '=', 'sale_costs.sale_id'))
                ->where('sales.status', 'completed')->whereDate('sales.sold_at', '>=', $range[0])->whereDate('sales.sold_at', '<=', $range[1])
                ->selectRaw("sales.number, sales.sold_at, COALESCE(customers.name, 'Vente comptoir') as customer, sales.total_amount, sales.amount_paid, sales.amount_due, sales.payment_status, (sales.total_amount - COALESCE(sale_costs.cost, 0)) as estimated_margin")
                ->orderBy('sales.sold_at'),
            'purchases' => Purchase::query()->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
                ->where('purchases.status', 'received')->whereDate('purchases.purchased_at', '>=', $range[0])->whereDate('purchases.purchased_at', '<=', $range[1])
                ->selectRaw('purchases.number, purchases.purchased_at, suppliers.name as supplier, purchases.total_amount, purchases.amount_paid, purchases.amount_due, purchases.payment_status')
                ->orderBy('purchases.purchased_at'),
            'stock' => Product::query()->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->selectRaw('products.sku, products.name, COALESCE(categories.name, \'\') as category, ROUND(products.stock_quantity) as stock_quantity, ROUND(products.minimum_stock) as minimum_stock, products.purchase_price, products.sale_price, (products.stock_quantity * products.purchase_price) as stock_value, products.status')
                ->orderBy('products.name'),
            'expenses' => Expense::query()->where('expenses.is_active', true)->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
                ->leftJoin('users', 'expenses.user_id', '=', 'users.id')->whereDate('expenses.spent_at', '>=', $range[0])->whereDate('expenses.spent_at', '<=', $range[1])
                ->selectRaw("expenses.spent_at, expense_categories.name as category, expenses.description, expenses.amount, COALESCE(expenses.payment_method, '') as payment_method, COALESCE(users.name, '') as user")
                ->orderBy('expenses.spent_at'),
            'movements' => StockMovement::query()->join('products', 'stock_movements.product_id', '=', 'products.id')
                ->leftJoin('users', 'stock_movements.user_id', '=', 'users.id')
                ->whereBetween('stock_movements.occurred_at', [$from->startOfDay(), $to->endOfDay()])
                ->selectRaw("stock_movements.occurred_at, products.sku, products.name as product, stock_movements.movement_type, ROUND(stock_movements.quantity) as quantity, ROUND(stock_movements.stock_before) as stock_before, ROUND(stock_movements.stock_after) as stock_after, COALESCE(stock_movements.reason, '') as reason, COALESCE(users.name, '') as user")
                ->orderBy('stock_movements.occurred_at'),
            default => throw new InvalidArgumentException('Type de rapport non disponible.'),
        };
    }
}
