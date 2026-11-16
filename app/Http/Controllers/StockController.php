<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockMovementRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(private readonly StockService $stockService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', StockMovement::class);
        $query = Product::with('category');
        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn ($builder) => $builder->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"));
        }
        if ($request->query('state') === 'out') {
            $query->where('stock_quantity', '<=', 0);
        } elseif ($request->query('state') === 'low') {
            $query->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'minimum_stock');
        }

        $sort = $request->query('sort');
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $query->orderBy(in_array($sort, ['name', 'stock_quantity', 'minimum_stock'], true) ? $sort : 'name', $direction);

        return view('stock.index', [
            'products' => $query->paginate(15)->withQueryString(),
            'selectableProducts' => Product::where('status', 'active')->orderBy('name')->get(['id', 'name', 'sku', 'stock_quantity']),
        ]);
    }

    public function movements(Request $request): View
    {
        $this->authorize('viewAny', StockMovement::class);
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ], [
            'from.date' => 'La date de début est invalide.',
            'to.date' => 'La date de fin est invalide.',
            'to.after_or_equal' => 'La date de fin doit être égale ou postérieure à la date de début.',
        ]);
        $query = StockMovement::with(['product', 'user']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }
        if (in_array($request->query('movement_type'), StockMovement::TYPES, true)) {
            $query->where('movement_type', $request->query('movement_type'));
        }
        if ($request->filled('from')) {
            $query->whereDate('occurred_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('occurred_at', '<=', $request->date('to'));
        }

        return view('stock.movements', [
            'movements' => $query->orderByDesc('occurred_at')->paginate(20)->withQueryString(),
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku']),
        ]);
    }

    public function storeMovement(StockMovementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $product = Product::findOrFail($data['product_id']);
        $this->stockService->record($product, $data['movement_type'], $data['quantity'], $request->user(), $data['reason']);

        return redirect()->route('stock.index')->with('status', 'Mouvement de stock enregistré avec succès.');
    }
}
