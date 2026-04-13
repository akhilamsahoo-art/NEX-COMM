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
    Schema::table('carts', function (Blueprint $table) {
        // We add tenant_id after user_id to keep the database organized
        $table->unsignedBigInteger('tenant_id')->nullable()->after('user_id');
        
        // Optional: If you want to ensure data integrity
        // $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
    });
}

public function down(): void
{
    Schema::table('carts', function (Blueprint $table) {
        $table->dropColumn('tenant_id');
    });
}
};
