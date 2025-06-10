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
        Schema::create('abouts_info', function (Blueprint $table) {
            $table->id();
            $table->string('cover')->nullable();
            $table->string('aboutTitle')->nullable();
            $table->text('aboutDescription')->nullable();
            $table->string('aboutImage')->nullable();
            $table->string('middleTitle')->nullable();
            $table->text('middleDescription')->nullable();
            $table->string('middleImage')->nullable();
            $table->string('bottomOneTitle')->nullable();
            $table->text('bottomOneDescription')->nullable();
            $table->string('bottomOneImageOne')->nullable();
            $table->string('bottomOneImageTwo')->nullable();
            $table->string('bottomTwoTitle')->nullable();
            $table->text('bottomTwoDescription')->nullable();
            $table->string('bottomTwoImageOne')->nullable();
            $table->string('bottomTwoImageTwo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abouts_info');
    }
};
