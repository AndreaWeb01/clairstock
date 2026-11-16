<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    public const STATUSES = ['draft', 'received', 'cancelled'];
    protected $fillable = ['number', 'supplier_id', 'user_id', 'purchased_at', 'status', 'subtotal', 'discount_amount', 'total_amount', 'amount_paid', 'amount_due', 'payment_status', 'notes'];
    protected function casts(): array { return ['purchased_at' => 'date', 'subtotal' => 'decimal:2', 'discount_amount' => 'decimal:2', 'total_amount' => 'decimal:2', 'amount_paid' => 'decimal:2', 'amount_due' => 'decimal:2']; }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(PurchaseItem::class); }
    public function payments(): \Illuminate\Database\Eloquent\Relations\MorphMany { return $this->morphMany(Payment::class, 'payable'); }
}
