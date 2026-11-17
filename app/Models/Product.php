<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    public const UNITS = ['piece', 'kg', 'g', 'litre', 'ml', 'carton', 'paquet', 'boite', 'autre'];
    public const STATUSES = ['active', 'inactive'];

    protected $fillable = [
        'category_id', 'name', 'sku', 'description', 'unit', 'purchase_price', 'sale_price',
        'minimum_stock', 'status', 'image_path',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock_quantity' => 'decimal:0',
            'minimum_stock' => 'decimal:0',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems(): HasMany { return $this->hasMany(SaleItem::class); }

    public function purchaseItems(): HasMany { return $this->hasMany(PurchaseItem::class); }

    public function stockState(): string
    {
        $stock = (float) $this->stock_quantity;

        if ($stock <= 0) {
            return 'out';
        }

        return $stock <= (float) $this->minimum_stock ? 'low' : 'normal';
    }
}
