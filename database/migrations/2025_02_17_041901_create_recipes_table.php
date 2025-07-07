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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('image')->nullable();
            $table->text('ingredients');
            $table->text('instructions');
            $table->integer('cooking_time')->default(30); // in minutes
            $table->integer('servings')->default(4);
            $table->string('difficulty')->default('medium'); // easy, medium, hard
            $table->string('category')->default('breakfast'); // breakfast, lunch, dinner, snack, dessert
            $table->boolean('is_featured')->default(false);
            $table->integer('views')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
