<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Expense::class);
        $query = Expense::query()->with(['category', 'user']);
        if ($search = trim((string) $request->query('search'))) $query->where('description', 'like', "%{$search}%");
        if ($request->filled('category_id') && ctype_digit((string) $request->query('category_id'))) $query->where('expense_category_id', $request->integer('category_id'));
        if ($request->filled('date_from') && strtotime((string) $request->query('date_from'))) $query->whereDate('spent_at', '>=', $request->query('date_from'));
        if ($request->filled('date_to') && strtotime((string) $request->query('date_to'))) $query->whereDate('spent_at', '<=', $request->query('date_to'));
        $filteredTotal = (clone $query)->reorder()->sum('amount');
        return view('expenses.index', [
            'expenses' => $query->latest('spent_at')->latest('id')->paginate(15)->withQueryString(),
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'filteredTotal' => $filteredTotal,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Expense::class);
        return view('expenses.form', ['expense' => new Expense(['spent_at' => today()]), 'categories' => ExpenseCategory::where('is_active', true)->orderBy('name')->get(), 'editing' => false]);
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('receipt');
        $data['user_id'] = $request->user()->id;
        if ($request->hasFile('receipt')) $data['receipt_path'] = $request->file('receipt')->store('expense-receipts', 'public');
        Expense::create($data);
        return redirect()->route('expenses.index')->with('status', 'Dépense enregistrée avec succès.');
    }

    public function edit(Expense $expense): View
    {
        $this->authorize('update', $expense);
        return view('expenses.form', ['expense' => $expense, 'categories' => ExpenseCategory::where('is_active', true)->orWhereKey($expense->expense_category_id)->orderBy('name')->get(), 'editing' => true]);
    }

    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $data = $request->safe()->except('receipt');
        $previousReceipt = null;
        if ($request->hasFile('receipt')) {
            $previousReceipt = $expense->receipt_path;
            $data['receipt_path'] = $request->file('receipt')->store('expense-receipts', 'public');
        }
        $expense->update($data);
        if ($previousReceipt) Storage::disk('public')->delete($previousReceipt);
        return redirect()->route('expenses.index')->with('status', 'Dépense mise à jour.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);
        if ($expense->receipt_path) Storage::disk('public')->delete($expense->receipt_path);
        $expense->delete();
        return redirect()->route('expenses.index')->with('status', 'Dépense supprimée.');
    }
}
