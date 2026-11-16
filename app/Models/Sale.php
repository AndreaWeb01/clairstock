<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = ['number', 'customer_id', 'user_id', 'sold_at', 'status', 'subtotal', 'discount_amount', 'total_amount', 'amount_paid', 'amount_due', 'payment_status', 'payment_method', 'notes'];
    protected function casts(): array { return ['sold_at' => 'date', 'subtotal' => 'decimal:2', 'discount_amount' => 'decimal:2', 'total_amount' => 'decimal:2', 'amount_paid' => 'decimal:2', 'amount_due' => 'decimal:2']; }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(SaleItem::class); }
    public function payments(): \Illuminate\Database\Eloquent\Relations\MorphMany { return $this->morphMany(Payment::class, 'payable'); }
}
