<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Sale::class);
        $query = Sale::query()->with(['customer', 'user']);
        if ($search = trim((string) $request->query('search'))) $query->where(fn ($q) => $q->where('number', 'like', "%{$search}%")->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")));
        if ($request->filled('status') && in_array($request->query('status'), ['paid','partial','unpaid'], true)) $query->where('payment_status', $request->query('status'));
        return view('sales.index', ['sales' => $query->latest('sold_at')->latest('id')->paginate(15)->withQueryString()]);
    }

    public function create(): View
    {
        $this->authorize('create', Sale::class);
        return view('sales.create', ['customers' => Customer::orderBy('name')->get(), 'products' => Product::where('status', 'active')->orderBy('name')->get()]);
    }

    public function store(StoreSaleRequest $request, SaleService $service): RedirectResponse
    {
        $sale = $service->create($request->validated(), $request->user());
        return redirect()->route('sales.show', $sale)->with('status', 'Vente enregistrée avec succès.');
    }

    public function show(Sale $sale): View
    {
        $this->authorize('view', $sale);
        return view('sales.show', ['sale' => $sale->load(['customer', 'user', 'items.product', 'payments.user'])]);
    }
}
