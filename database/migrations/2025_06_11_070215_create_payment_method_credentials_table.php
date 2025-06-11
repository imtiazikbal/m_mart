<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_method_credentials', function (Blueprint $table) {
             $table->id();
            $table->string('payment_method_name')->nullable();
            $table->string('ssl_store_id')->nullable();
            $table->string('ssl_store_passwd')->nullable();
            $table->string('currency')->nullable();
            $table->string('ssl_ipn_url')->nullable();
            $table->string('ssl_init_url')->nullable();
            $table->string('ssl_signature_key')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_method_credentials');
    }
};
