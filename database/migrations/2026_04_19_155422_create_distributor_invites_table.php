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
        Schema::create('distributor_invites', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token')->unique(); // hashed SHA-256 token
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->dateTime('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributor_invites');
    }
};
