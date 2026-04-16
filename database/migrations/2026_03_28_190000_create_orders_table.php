<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_address_id')->nullable()->constrained('user_addresses')->nullOnDelete();


            // Payment and order status
            $table->string('payment_method')->nullable(); // e.g., 'mpesa', 'card', 'paypal'
            $table->string('payment_status')->default('pending'); // e.g., 'pending', 'paid', 'failed', 'refunded'
            $table->string('order_status')->default('pending'); // e.g., 'pending', 'processing', 'shipped', 'delivered', 'cancelled'
            $table->string('currency', 10)->default('KSH');
            $table->unsignedInteger('items_count')->default(0);

            // Pricing details
            $table->decimal('subtotal_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('wallet_applied_amount', 12, 2)->default(0);
            $table->decimal('shipping_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);

            // Tracking and Shipping partners (nullable, populated when shipped)
            $table->string('tracking_number')->nullable();
            $table->string('tracking_link', 1000)->nullable();
            $table->string('shipping_partner')->nullable();

            // Transaction & reference fields (optional, for future integration)
            $table->string('transaction_id')->nullable(); // For payment gateway transaction reference
            $table->string('order_number')->nullable(); // Can generate a unique order number for display/reference

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
