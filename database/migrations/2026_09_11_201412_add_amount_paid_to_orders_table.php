<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds two audit columns to orders:
     *   - amount_paid: the amount Safaricom actually collected (may differ from total)
     *   - payment_notes: human-readable note when amount doesn't match (e.g. underpayment details)
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('amount_paid', 10, 2)->nullable()->after('shipping');
            $table->text('payment_notes')->nullable()->after('amount_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'payment_notes']);
        });
    }
};
