<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = ['expense_category_id', 'user_id', 'description', 'amount', 'spent_at', 'payment_method', 'receipt_path', 'is_active'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'spent_at' => 'date', 'is_active' => 'boolean']; }
    public function category(): BelongsTo { return $this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
