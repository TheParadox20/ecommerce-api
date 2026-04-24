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
        Schema::table('brands', function (Blueprint $table) {
            $table->decimal('min_order_amount', 12, 2)->default(0)->after('instagram_url');
            $table->decimal('max_order_amount', 12, 2)->nullable()->after('min_order_amount');
        });

        Schema::table('product_variations', function (Blueprint $table) {
            $table->decimal('weight_kg', 10, 3)->default(0)->after('image');
            $table->integer('min_order_quantity')->default(0)->after('weight_kg');
            $table->boolean('is_bulk')->default(false)->after('min_order_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['min_order_amount', 'max_order_amount']);
        });

        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropColumn(['weight_kg', 'min_order_quantity', 'is_bulk']);
        });
    }
};
