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
        
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
        
            $table->string('name'); // Store name
            $table->string('slug')->unique(); // unique identifier (URL friendly)
        
            // ✅ NEW (important for SaaS)
            $table->boolean('is_active')->default(true); // enable/disable store
            $table->string('email')->nullable(); // store contact email
            $table->string('logo')->nullable(); // store branding (future use)
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
