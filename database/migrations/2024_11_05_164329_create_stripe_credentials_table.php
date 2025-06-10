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
        Schema::create('stripe_credentials', function (Blueprint $table) {
            $table->string('apiKey')->unique();
            $table->string('apiSecret')->unique();
            $table->string('successUrl')->nullable();
            $table->string('cancelUrl')->nullable();
            $table->string('failedUurl')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_credentials');
    }
};
