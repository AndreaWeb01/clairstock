<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);
        $query = Category::query()->withCount('products');

        if ($search = trim((string) $request->query('search'))) {
            $query->where('name', 'like', "%{$search}%");
        }
        if (in_array($request->query('status'), ['active', 'inactive'], true)) {
            $query->where('is_active', $request->query('status') === 'active');
        }

        $sort = $request->query('sort');
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $query->orderBy(in_array($sort, ['name', 'products_count', 'created_at'], true) ? $sort : 'name', $direction);

        return view('categories.index', ['categories' => $query->paginate(15)->withQueryString()]);
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('categories.create', ['category' => new Category(['is_active' => true])]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()->route('categories.index')->with('status', 'Catégorie créée avec succès.');
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')->with('status', 'Catégorie mise à jour avec succès.');
    }

}
