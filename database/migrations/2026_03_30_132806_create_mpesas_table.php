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
        Schema::create('mpesas', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('checkout_request_id');
            $table->string('result_code');
            $table->string('result_desc');
            $table->string('merchant_request_id');
            $table->string('status')->default('pending');
            $table->string('phone');
            $table->string('amount');
            $table->string('account_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpesas');
    }
};
