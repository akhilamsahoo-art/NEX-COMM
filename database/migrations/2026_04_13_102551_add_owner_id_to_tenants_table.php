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
    Schema::table('tenants', function (Blueprint $table) {
        // Add owner_id as a foreign key pointing to the users table
        $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('cascade')->after('id');
    });
}

public function down()
{
    Schema::table('tenants', function (Blueprint $table) {
        $table->dropForeign(['owner_id']);
        $table->dropColumn('owner_id');
    });
}
};
