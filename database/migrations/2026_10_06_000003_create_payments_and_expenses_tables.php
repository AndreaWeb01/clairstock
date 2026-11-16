<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->morphs('payable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('method', 30);
            $table->date('paid_at')->index();
            $table->string('reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['method', 'paid_at']);
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 200);
            $table->decimal('amount', 15, 2);
            $table->date('spent_at')->index();
            $table->string('payment_method', 30)->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamps();
            $table->index(['expense_category_id', 'spent_at']);
        });

        // Preserve payments entered on sales before this ledger existed.
        foreach (DB::table('sales')->where('amount_paid', '>', 0)->get() as $sale) {
            DB::table('payments')->insert([
                'payable_type' => 'App\\Models\\Sale', 'payable_id' => $sale->id,
                'user_id' => $sale->user_id, 'amount' => $sale->amount_paid,
                'method' => $sale->payment_method ?: 'other', 'paid_at' => $sale->sold_at,
                'reference' => null, 'notes' => 'Paiement initial enregistré avec la vente',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('payments');
    }
};
