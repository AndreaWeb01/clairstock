<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    public const TYPES = ['entry', 'exit', 'adjustment_positive', 'adjustment_negative', 'sale', 'purchase'];

    protected $fillable = [
        'product_id', 'user_id', 'movement_type', 'quantity', 'stock_before', 'stock_after',
        'reason', 'reference_type', 'reference_id', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:0',
            'stock_before' => 'decimal:0',
            'stock_after' => 'decimal:0',
            'occurred_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function typeLabel(): string
    {
        return match ($this->movement_type) {
            'entry' => 'Entrée',
            'exit' => 'Sortie',
            'adjustment_positive' => 'Ajustement positif',
            'adjustment_negative' => 'Ajustement négatif',
            'sale' => 'Vente',
            'purchase' => 'Achat',
            default => 'Autre',
        };
    }
}
