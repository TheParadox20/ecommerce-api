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
        Schema::create('offer_bundles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('banner_image');
            $table->decimal('bundle_price', 10, 2);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('offer_bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_bundle_id')->constrained('offer_bundles')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_variation_id')->nullable()->constrained('product_variations')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('override_price', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_bundle_items');
        Schema::dropIfExists('offer_bundles');
    }
};
