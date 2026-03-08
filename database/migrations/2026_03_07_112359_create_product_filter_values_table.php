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
        if (Schema::hasTable('product_filter_values')) {
            return;
        }

        Schema::create('product_filter_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('filter_value_id');
            $table->timestamps();
            $table->index('product_id');
            $table->index('filter_value_id');
            $table->unique(['product_id', 'filter_value_id'], 'product_filter_values_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_filter_values');
    }
};
