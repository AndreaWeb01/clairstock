<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function data(bool $administrator = true): array
    {
        $today = CarbonImmutable::today();
        $monthStart = $today->startOfMonth();
        $monthEnd = $today->endOfMonth();
        $days = collect(range(6, 0))->map(fn ($daysAgo) => $today->subDays($daysAgo));
        $dailySales = Sale::query()->selectRaw('DATE(sold_at) as sale_date, SUM(total_amount) as total')
            ->whereDate('sold_at', '>=', $days->first()->toDateString())
            ->whereDate('sold_at', '<=', $today->toDateString())
            ->where('status', 'completed')->groupByRaw('DATE(sold_at)')->pluck('total', 'sale_date');
        $salesByDay = $days->map(fn ($day) => ['date' => $day, 'total' => (float) ($dailySales[$day->toDateString()] ?? 0)]);
        $categorySales = SaleItem::query()->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereDate('sales.sold_at', '>=', $monthStart->toDateString())
            ->whereDate('sales.sold_at', '<=', $monthEnd->toDateString())
            ->where('sales.status', 'completed')->selectRaw("COALESCE(categories.name, 'Sans catégorie') as name, SUM(sale_items.line_total) as total")
            ->groupBy('categories.name')->orderByDesc('total')->limit(6)->get();
        $topProducts = SaleItem::query()->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')->whereDate('sales.sold_at', '>=', $today->subDays(29)->toDateString())->whereDate('sales.sold_at', '<=', $today->toDateString())
            ->selectRaw('sale_items.product_name, sale_items.sku, SUM(sale_items.quantity) as quantity, SUM(sale_items.line_total) as total')
            ->groupBy('sale_items.product_name', 'sale_items.sku')->orderByDesc('quantity')->limit(5)->get();
        $margin = (float) (SaleItem::query()->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')->whereDate('sales.sold_at', '>=', $monthStart->toDateString())->whereDate('sales.sold_at', '<=', $monthEnd->toDateString())
            ->selectRaw('COALESCE(SUM(sale_items.line_total - (sale_items.quantity * sale_items.unit_cost)), 0) as value')->value('value') ?? 0);
        $expensesMonth = $administrator
            ? (float) Expense::query()->where('is_active', true)->whereDate('spent_at', '>=', $monthStart->toDateString())->whereDate('spent_at', '<=', $monthEnd->toDateString())->sum('amount')
            : 0.0;

        return [
            'todayRevenue' => (float) Sale::query()->whereDate('sold_at', $today)->where('status', 'completed')->sum('total_amount'),
            'monthRevenue' => (float) Sale::query()->whereDate('sold_at', '>=', $monthStart->toDateString())->whereDate('sold_at', '<=', $monthEnd->toDateString())->where('status', 'completed')->sum('total_amount'),
            'monthSalesCount' => Sale::query()->whereDate('sold_at', '>=', $monthStart->toDateString())->whereDate('sold_at', '<=', $monthEnd->toDateString())->where('status', 'completed')->count(),
            'productCount' => Product::count(),
            'stockValue' => (float) (Product::query()->selectRaw('COALESCE(SUM(stock_quantity * purchase_price), 0) as value')->value('value') ?? 0),
            'estimatedProfit' => $administrator ? $margin - $expensesMonth : null,
            'expensesMonth' => $expensesMonth,
            'outOfStock' => Product::query()->where('status', 'active')->where('stock_quantity', '<=', 0)->orderBy('name')->limit(8)->get(),
            'lowStock' => Product::query()->where('status', 'active')->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'minimum_stock')->orderBy('stock_quantity')->limit(8)->get(),
            'outOfStockCount' => Product::query()->where('status', 'active')->where('stock_quantity', '<=', 0)->count(),
            'lowStockCount' => Product::query()->where('status', 'active')->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'minimum_stock')->count(),
            'overdueSales' => Sale::query()->with('customer')->where('status', 'completed')->where('amount_due', '>', 0)->whereDate('sold_at', '<=', $today->subDays(30))->orderBy('sold_at')->limit(8)->get(),
            'overduePurchases' => $administrator ? Purchase::query()->with('supplier')->where('status', 'received')->where('amount_due', '>', 0)->whereDate('purchased_at', '<=', $today->subDays(30))->orderBy('purchased_at')->limit(8)->get() : collect(),
            'overdueSalesCount' => Sale::query()->where('status', 'completed')->where('amount_due', '>', 0)->whereDate('sold_at', '<=', $today->subDays(30))->count(),
            'overduePurchasesCount' => $administrator ? Purchase::query()->where('status', 'received')->where('amount_due', '>', 0)->whereDate('purchased_at', '<=', $today->subDays(30))->count() : 0,
            'salesByDay' => $salesByDay,
            'categorySales' => $categorySales,
            'topProducts' => $topProducts,
            'recentSales' => Sale::with(['customer', 'user'])->latest('sold_at')->latest('id')->limit(5)->get(),
            'recentPurchases' => Purchase::with(['supplier', 'user'])->latest('purchased_at')->latest('id')->limit(5)->get(),
            'recentMovements' => StockMovement::with(['product', 'user'])->latest('occurred_at')->limit(6)->get(),
        ];
    }
}
