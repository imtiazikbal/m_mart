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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('price', 10, 2)->default(0); // Stored as a decimal
            $table->decimal('discountPercent', 5, 2)->default(0); // Stored as a decimal, max 99.99%
            $table->string('displayImageSrc')->nullable(); // File path
            $table->string('hoverImageSrc')->nullable(); // File path
            $table->unsignedBigInteger('categoryId'); // Foreign key reference
            $table->unsignedBigInteger('subCategoryId')->nullable(); // Foreign key reference
            $table->unsignedBigInteger('subSubCategoryId')->nullable(); // Foreign key reference
            $table->integer('productQuantity')->default(0); // Stored as an integer
            $table->string('material')->nullable(); // Optional
            $table->string('size')->nullable(); // Optional
            $table->string('capacity')->nullable(); // Optional
            $table->boolean('isFeatured')->default(0); // Boolean for true/false
            $table->boolean('isBestSelling')->default(0);
            $table->boolean('isFestiveDelights')->default(0);
            $table->boolean('isRecommended')->default(0);
            $table->longText('description')->nullable(); // For long descriptions
            $table->string('status')->default('Active');

                // soft delete
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
