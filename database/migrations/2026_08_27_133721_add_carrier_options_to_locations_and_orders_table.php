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
        Schema::table('locations', function (Blueprint $table) {
            $table->decimal('rider_fee', 10, 2)->nullable()->after('delivery_fee');
            $table->decimal('sacco_fee', 10, 2)->nullable()->after('rider_fee');
            $table->string('rider_name')->nullable()->after('sacco_rider');
            $table->string('sacco_name')->nullable()->after('rider_name');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('carrier_type')->nullable()->after('delivery_method');
            $table->string('carrier_name')->nullable()->after('carrier_type');
            $table->string('delivery_county')->nullable()->after('delivery_zone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['rider_fee', 'sacco_fee', 'rider_name', 'sacco_name']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['carrier_type', 'carrier_name', 'delivery_county']);
        });
    }
};
