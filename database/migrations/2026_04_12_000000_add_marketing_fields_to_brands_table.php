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
        Schema::table('brands', function (Blueprint $barcode) {
            $barcode->string('logo')->nullable()->after('name');
            $barcode->string('description')->nullable()->after('logo');
            $barcode->string('color_hex', 7)->nullable()->default('#111111')->after('description');
            $barcode->boolean('is_active')->default(true)->after('color_hex');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['logo', 'description', 'color_hex', 'is_active']);
        });
    }
};
