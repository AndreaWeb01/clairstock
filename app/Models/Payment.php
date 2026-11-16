<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    protected $fillable = ['user_id', 'amount', 'method', 'paid_at', 'reference', 'notes'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'paid_at' => 'date']; }
    public function payable(): MorphTo { return $this->morphTo(); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function methodLabel(): string { return ['cash' => 'Espèces', 'card' => 'Carte', 'transfer' => 'Virement', 'mobile_money' => 'Mobile money', 'other' => 'Autre'][$this->method] ?? 'Autre'; }
}
