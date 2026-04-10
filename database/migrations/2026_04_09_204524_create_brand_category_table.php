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
        Schema::create('brand_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->timestamps();
        });

        // Migrate existing data from brands.category_id to brand_category pivot table
        $brands = DB::table('brands')->whereNotNull('category_id')->get();
        foreach ($brands as $brand) {
            DB::table('brand_category')->insert([
                'brand_id' => $brand->id,
                'category_id' => $brand->category_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Optional: Drop category_id from brands table
        Schema::table('brands', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
        });

        // Reverse data migraiton (if needed, but usually pivot is enough)
        $pivotData = DB::table('brand_category')->get();
        foreach ($pivotData as $data) {
            DB::table('brands')->where('id', $data->brand_id)->update(['category_id' => $data->category_id]);
        }

        Schema::dropIfExists('brand_category');
    }
};
