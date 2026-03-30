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
        if (!Schema::hasColumn('recipes', 'slug')) {
            Schema::table('recipes', function (Blueprint $table) {
                $table->string('slug')->nullable()->unique()->after('title');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('recipes', 'slug')) {
            Schema::table('recipes', function (Blueprint $table) {
                $table->dropUnique('recipes_slug_unique');
                $table->dropColumn('slug');
            });
        }
    }
};
