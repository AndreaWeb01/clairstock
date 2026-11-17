<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Supplier::class);
        $received = fn ($purchases) => $purchases->where('status', 'received');
        $query = Supplier::query()->withCount(['purchases' => $received])->withSum(['purchases' => $received], 'total_amount');
        $status = $request->query('status', 'active');
        if (in_array($status, ['active', 'inactive'], true)) $query->where('is_active', $status === 'active');
        if ($search = trim((string) $request->query('search'))) $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('company', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
        return view('suppliers.index', ['suppliers' => $query->orderBy('name')->paginate(15)->withQueryString()]);
    }

    public function create(): View { $this->authorize('create', Supplier::class); return view('suppliers.form', ['supplier' => new Supplier(['is_active' => true]), 'editing' => false]); }
    public function store(SupplierRequest $request): RedirectResponse { Supplier::create($request->validated()); return redirect()->route('suppliers.index')->with('status', 'Fournisseur créé avec succès.'); }
    public function edit(Supplier $supplier): View { $this->authorize('update', $supplier); return view('suppliers.form', ['supplier' => $supplier, 'editing' => true]); }
    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse { $supplier->update($request->validated()); return redirect()->route('suppliers.index')->with('status', 'Fournisseur mis à jour avec succès.'); }
}
