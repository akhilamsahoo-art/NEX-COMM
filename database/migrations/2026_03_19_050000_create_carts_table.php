<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('carts', function (Blueprint $table) {
        $table->id();
        // If you are using multi-tenancy:
        $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
        $table->boolean('is_checked_out')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
