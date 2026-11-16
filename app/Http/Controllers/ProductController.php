<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Services\SettingService;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);
        $query = Product::query()->with('category');

        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn ($builder) => $builder->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if (in_array($request->query('status'), Product::STATUSES, true)) {
            $query->where('status', $request->query('status'));
        }
        if ($request->query('stock') === 'out') {
            $query->where('stock_quantity', '<=', 0);
        } elseif ($request->query('stock') === 'low') {
            $query->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'minimum_stock');
        } elseif ($request->query('stock') === 'normal') {
            $query->whereColumn('stock_quantity', '>', 'minimum_stock');
        }

        $sort = $request->query('sort');
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $query->orderBy(in_array($sort, ['name', 'sku', 'stock_quantity', 'sale_price', 'created_at'], true) ? $sort : 'name', $direction);

        return view('products.index', [
            'products' => $query->paginate(15)->withQueryString(),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(SettingService $settings): View
    {
        $this->authorize('create', Product::class);

        return view('products.create', [
            'product' => new Product(['unit' => 'piece', 'status' => 'active', 'minimum_stock' => $settings->get('default_minimum_stock', '0.000')]),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        $product = Product::create($data);

        return redirect()->route('products.show', $product)->with('status', 'Produit créé avec succès. Le stock initial est à zéro.');
    }

    public function show(Product $product): View
    {
        $this->authorize('view', $product);
        $product->load('category');

        return view('products.show', [
            'product' => $product,
            'movements' => $product->stockMovements()->with('user')->latest('occurred_at')->paginate(10),
        ]);
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('products.edit', [
            'product' => $product,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->safe()->except('image');
        if ($request->hasFile('image')) {
            $previousImage = $product->image_path;
            $data['image_path'] = $request->file('image')->store('products', 'public');
        } else {
            $previousImage = null;
        }
        $product->update($data);
        if ($previousImage) {
            Storage::disk('public')->delete($previousImage);
        }

        return redirect()->route('products.show', $product)->with('status', 'Produit mis à jour avec succès.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        if ($product->stockMovements()->exists() || $product->saleItems()->exists() || $product->purchaseItems()->exists()) {
            return back()->with('error', 'Ce produit possède un historique de stock, de vente ou d’achat et ne peut pas être supprimé. Vous pouvez le désactiver.');
        }

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();

        return redirect()->route('products.index')->with('status', 'Produit supprimé avec succès.');
    }
}
