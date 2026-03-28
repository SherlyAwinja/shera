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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_address_id')->nullable()->constrained('user_addresses')->nullOnDelete();
            $table->string('order_uuid', 36)->unique();
            $table->string('order_number', 40)->unique();
            $table->string('payment_method', 30);
            $table->string('payment_status', 30)->default('pending');
            $table->string('order_status', 30)->default('placed');
            $table->string('currency', 10)->default('KSH');
            $table->unsignedInteger('items_count')->default(0);
            $table->decimal('subtotal_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('wallet_applied_amount', 12, 2)->default(0);
            $table->decimal('shipping_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->string('address_label', 100)->nullable();
            $table->string('recipient_name', 150);
            $table->string('recipient_phone', 30);
            $table->string('email')->nullable();
            $table->string('country', 191);
            $table->string('county', 191)->nullable();
            $table->string('sub_county', 191)->nullable();
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('estate', 191)->nullable();
            $table->text('landmark')->nullable();
            $table->string('pincode', 20);
            $table->string('shipping_zone', 100)->nullable();
            $table->string('shipping_eta', 100)->nullable();
            $table->json('shipping_quote')->nullable();
            $table->timestamp('placed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
