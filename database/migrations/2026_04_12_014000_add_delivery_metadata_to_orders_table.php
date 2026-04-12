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
        Schema::table('orders', function (Blueprint $blueprint) {
            $blueprint->string('delivery_method')->nullable()->after('payment_method');
            $blueprint->string('pickup_station')->nullable()->after('delivery_method');
            $blueprint->date('expected_shipping_date')->nullable()->after('pickup_station');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['delivery_method', 'pickup_station', 'expected_shipping_date']);
        });
    }
};
