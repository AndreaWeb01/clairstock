<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('stock_quantity', 12, 0)->default(0)->change();
            $table->decimal('minimum_stock', 12, 0)->default(0)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('quantity', 12, 0)->change();
            $table->decimal('stock_before', 12, 0)->change();
            $table->decimal('stock_after', 12, 0)->change();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('quantity', 12, 0)->change();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('quantity', 12, 0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('stock_quantity', 12, 3)->default(0)->change();
            $table->decimal('minimum_stock', 12, 3)->default(0)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('quantity', 12, 3)->change();
            $table->decimal('stock_before', 12, 3)->change();
            $table->decimal('stock_after', 12, 3)->change();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('quantity', 12, 3)->change();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('quantity', 12, 3)->change();
        });
    }
};
