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
    Schema::table('orders', function (Blueprint $table) {
        // 1. Rename 'status' to 'order_status' ONLY if the old name exists
        if (Schema::hasColumn('orders', 'status') && !Schema::hasColumn('orders', 'order_status')) {
            $table->renameColumn('status', 'order_status');
        }

        // 2. Ensure tenant_id exists (Crucial for Seller Panel)
        if (!Schema::hasColumn('orders', 'tenant_id')) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('user_id');
        }
        
        // 3. Add payment_status only if it doesn't exist yet
        if (!Schema::hasColumn('orders', 'payment_status')) {
            $table->string('payment_status')->default('pending')->after('order_status');
        }

        // 4. Add shipment_status only if it doesn't exist yet
        if (!Schema::hasColumn('orders', 'shipment_status')) {
            $table->string('shipment_status')->default('pending')->after('payment_status');
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
