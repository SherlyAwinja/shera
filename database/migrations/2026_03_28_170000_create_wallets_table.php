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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action', 20);
            $table->decimal('amount', 12, 2);
            $table->decimal('signed_amount', 12, 2);
            $table->text('description')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'status', 'expiry_date']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
