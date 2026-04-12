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
        Schema::table('blog_comments', function (Blueprint $table) {
            $table->string('guest_name')->nullable()->after('user_id');
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->boolean('is_approved')->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_comments', function (Blueprint $table) {
            $table->dropColumn('guest_name');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->boolean('is_approved')->default(true)->change();
        });
    }
};
