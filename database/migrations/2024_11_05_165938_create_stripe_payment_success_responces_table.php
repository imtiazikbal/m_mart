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
        Schema::create('stripe_payment_success_responces', function (Blueprint $table) {
            $table->id();
            $table->string('checkoutSessionId')->unique();
            $table->string('currency');
            $table->integer('amountSubtotal');
            $table->integer('amountTotal');
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('expiresAt')->nullable();
            $table->string('paymentIntent')->nullable();
            $table->string('paymentStatus');
            $table->string('customerName')->nullable();
            $table->string('customerEmail')->nullable();
            $table->string('customerCity')->nullable();
            $table->string('customerCountry')->nullable();
            $table->string('customerLine1')->nullable();
            $table->string('customerPostal_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_payment_success_responces');
    }
};
