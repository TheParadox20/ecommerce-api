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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('influencer_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->nullable(); // bank, mpesa, etc
            $table->string('reference')->nullable();
            $table->string('status')->default('paid'); // paid, processed
            $table->timestamps();
        });

        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('influencer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('payout_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending'); // pending, earned (order completed), paid, cancelled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('payouts');
    }
};
