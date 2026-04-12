<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop existing foreign keys
            $table->dropForeign(['category_id']);
            $table->dropForeign(['brand_id']);

            // Modify columns to be nullable and add new foreign keys with set null behavior
            $table->foreignId('category_id')
                ->nullable()
                ->change()
                ->constrained('categories')
                ->nullOnDelete();

            $table->foreignId('brand_id')
                ->nullable()
                ->change()
                ->constrained('brands')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['brand_id']);

            // Revert to original cascade behavior
            $table->foreignId('category_id')
                ->nullable(false)
                ->change()
                ->constrained('categories')
                ->onDelete('cascade');

            $table->foreignId('brand_id')
                ->nullable()
                ->change()
                ->constrained('brands')
                ->onDelete('cascade');
        });
    }
};
