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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('showInHeaderBar')->default('0');
            $table->string('showInIconBar')->default('0');

            $table->string('showInProductBar')->default('0');
            $table->string('icon')->nullable();
            $table->string('cover')->nullable();
            $table->string('thumbnail')->nullable();
            

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
        Schema::dropIfExists('categories');
    }
};
