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
        Schema::create('invoice_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoiceId');
            $table->unsignedBigInteger('productId');
            $table->unsignedBigInteger('userId');
            $table->decimal('salePrice', 10, 2); // Use decimal for currency values
            $table->integer('quantity'); // Add quantity to track the number of items

            $table->timestamps(); // Adds created_at and updated_at columns
            // Optional: Indexes for performance
            $table->index('invoiceId');
            $table->index('productId');
            $table->index('userId');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_products');
    }
};
