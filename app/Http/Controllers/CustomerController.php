<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);
        $query = Customer::query()->withCount('sales')->withSum('sales', 'total_amount');
        $status = $request->query('status', 'active');
        if (in_array($status, ['active', 'inactive'], true)) $query->where('is_active', $status === 'active');
        if ($search = trim((string) $request->query('search'))) $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        return view('customers.index', ['customers' => $query->orderBy('name')->paginate(15)->withQueryString()]);
    }

    public function create(): View { $this->authorize('create', Customer::class); return view('customers.form', ['customer' => new Customer(['is_active' => true]), 'editing' => false]); }
    public function store(CustomerRequest $request): RedirectResponse { $customer = Customer::create($request->validated()); return redirect()->route('customers.index')->with('status', 'Client créé avec succès.'); }
    public function edit(Customer $customer): View { $this->authorize('update', $customer); return view('customers.form', ['customer' => $customer, 'editing' => true]); }
    public function update(CustomerRequest $request, Customer $customer): RedirectResponse { $customer->update($request->validated()); return redirect()->route('customers.index')->with('status', 'Client mis à jour avec succès.'); }
}
