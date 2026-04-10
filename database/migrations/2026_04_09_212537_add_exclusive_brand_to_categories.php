<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('id')->constrained('brands')->onDelete('cascade');
        });

        // Data Migration: brand_category -> categories.brand_id
        if (Schema::hasTable('brand_category')) {
            $links = DB::table('brand_category')->get();
            foreach ($links as $link) {
                // Since it's now exclusive, we assign the brand to the category.
                // If a category was shared, it joins the FIRST brand encounter.
                DB::table('categories')
                    ->where('id', $link->category_id)
                    ->whereNull('brand_id')
                    ->update(['brand_id' => $link->brand_id]);
            }
            Schema::dropIfExists('brand_category');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('brand_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->timestamps();
        });

        $categories = DB::table('categories')->whereNotNull('brand_id')->get();
        foreach ($categories as $cat) {
            DB::table('brand_category')->insert([
                'brand_id' => $cat->brand_id,
                'category_id' => $cat->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropColumn('brand_id');
        });
    }
};
