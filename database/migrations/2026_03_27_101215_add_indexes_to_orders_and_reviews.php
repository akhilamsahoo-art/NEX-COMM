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
        // 1. Add index to the orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->index('status'); 
        });

        // 2. Add index to the reviews table
        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['product_id', 'rating']); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'rating']);
        });
    }
};