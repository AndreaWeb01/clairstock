<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name', 180)->index();
            $table->string('sku', 80)->unique();
            $table->text('description')->nullable();
            $table->string('unit', 30)->default('piece');
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->decimal('stock_quantity', 12, 3)->default(0);
            $table->decimal('minimum_stock', 12, 3)->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->string('image_path')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'status']);
            $table->index(['stock_quantity', 'minimum_stock']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
