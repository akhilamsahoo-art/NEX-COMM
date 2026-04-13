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
    Schema::table('products', function (Blueprint $table) {
        // Use unsignedBigInteger or foreignId
        $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
        
        // Optional: Add the foreign key constraint
        $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
    });
}

public function down()
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropForeign(['tenant_id']);
        $table->dropColumn('tenant_id');
    });
}
};
