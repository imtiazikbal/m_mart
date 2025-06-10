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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->decimal('total', 10, 2); // Use decimal for currency values
            $table->decimal('vat', 10, 2)->default(0); // Use decimal for VAT amounts
            $table->decimal('payable', 10, 2); // Use decimal for the total amount payable
            $table->string('cusDetails', 500); // Customer details as a string
            $table->string('tranId', 100); // Transaction ID as a string
            $table->string('paymentStatus'); // Payment status (e.g., pending, completed)
            $table->string('paymentMethod'); // Payment method (e.g., Stripe, PayPal)
            $table->string('coupon')->nullable(); // Coupon code (optional)
            $table->unsignedBigInteger('userId'); // User ID foreign key
            // Optional: Indexes for performance optimization
            $table->index('userId');

            $table->string('status')->default('Pending');

            $table->timestamps(); // Created_at and updated_at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
