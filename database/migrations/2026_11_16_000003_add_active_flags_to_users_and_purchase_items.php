<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->index();
            });
        }

        if (! Schema::hasColumn('purchase_items', 'is_active')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_active')) Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_active'));
        if (Schema::hasColumn('purchase_items', 'is_active')) Schema::table('purchase_items', fn (Blueprint $table) => $table->dropColumn('is_active'));
    }
};
