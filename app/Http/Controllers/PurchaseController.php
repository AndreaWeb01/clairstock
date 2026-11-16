<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Purchase::class);
        $query = Purchase::query()->with(['supplier', 'user']);
        if ($search = trim((string) $request->query('search'))) $query->where(fn ($q) => $q->where('number', 'like', "%{$search}%")->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$search}%")));
        if ($request->filled('status') && in_array($request->query('status'), Purchase::STATUSES, true)) $query->where('status', $request->query('status'));
        return view('purchases.index', ['purchases' => $query->latest('purchased_at')->latest('id')->paginate(15)->withQueryString()]);
    }

    public function create(): View
    {
        $this->authorize('create', Purchase::class);
        return view('purchases.create', ['suppliers' => Supplier::orderBy('name')->get(), 'products' => Product::orderBy('name')->get()]);
    }

    public function store(StorePurchaseRequest $request, PurchaseService $service): RedirectResponse
    {
        $purchase = $service->create($request->validated(), $request->user());
        return redirect()->route('purchases.show', $purchase)->with('status', $purchase->status === 'received' ? 'Achat reçu et stock mis à jour.' : 'Brouillon d’achat enregistré. Le stock reste inchangé.');
    }

    public function show(Purchase $purchase): View
    {
        $this->authorize('view', $purchase);
        return view('purchases.show', ['purchase' => $purchase->load(['supplier', 'user', 'items.product', 'payments.user'])]);
    }

    public function edit(Purchase $purchase): View
    {
        $this->authorize('update', $purchase);
        return view('purchases.create', [
            'purchase' => $purchase->load('items'), 'editing' => true,
            'suppliers' => Supplier::orderBy('name')->get(), 'products' => Product::orderBy('name')->get(),
        ]);
    }

    public function update(StorePurchaseRequest $request, Purchase $purchase, PurchaseService $service): RedirectResponse
    {
        $purchase = $service->updateDraft($purchase, $request->validated());
        return redirect()->route('purchases.show', $purchase)->with('status', 'Brouillon d’achat mis à jour. Le stock reste inchangé.');
    }

    public function receive(Purchase $purchase, PurchaseService $service): RedirectResponse
    {
        $this->authorize('receive', $purchase);
        $service->receive($purchase, request()->user());
        return back()->with('status', 'Achat réceptionné et stock mis à jour.');
    }

    public function cancel(Purchase $purchase, PurchaseService $service): RedirectResponse
    {
        $this->authorize('cancel', $purchase);
        $service->cancel($purchase, request()->user());
        return back()->with('status', 'Achat annulé. Les mouvements correctifs de stock ont été enregistrés si nécessaire.');
    }
}
