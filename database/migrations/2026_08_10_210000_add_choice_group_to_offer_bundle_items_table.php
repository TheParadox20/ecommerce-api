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
        Schema::table('offer_bundle_items', function (Blueprint $table) {
            $table->integer('choice_group')->nullable()->after('override_price');
            $table->boolean('is_required')->default(true)->after('choice_group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offer_bundle_items', function (Blueprint $table) {
            $table->dropColumn(['choice_group', 'is_required']);
        });
    }
};
