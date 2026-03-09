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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('payment_reference');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->foreignId('shipment_id')->nullable()->after('longitude')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shipment_id']);
            $table->dropColumn(['latitude', 'longitude', 'shipment_id']);
        });
    }
};
