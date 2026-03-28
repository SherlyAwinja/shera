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
        if (! Schema::hasTable('coupons')) {
            return;
        }

        Schema::table('coupons', function (Blueprint $table) {
            if (! Schema::hasColumn('coupons', 'coupon_option')) {
                $table->string('coupon_option')->after('id');
            }

            if (! Schema::hasColumn('coupons', 'coupon_code')) {
                $table->string('coupon_code')->unique()->after('coupon_option');
            }

            if (! Schema::hasColumn('coupons', 'categories')) {
                $table->json('categories')->nullable()->after('coupon_code');
            }

            if (! Schema::hasColumn('coupons', 'brands')) {
                $table->json('brands')->nullable()->after('categories');
            }

            if (! Schema::hasColumn('coupons', 'users')) {
                $table->json('users')->nullable()->after('brands');
            }

            if (! Schema::hasColumn('coupons', 'coupon_type')) {
                $table->string('coupon_type')->after('users');
            }

            if (! Schema::hasColumn('coupons', 'amount_type')) {
                $table->string('amount_type')->after('coupon_type');
            }

            if (! Schema::hasColumn('coupons', 'amount')) {
                $table->decimal('amount', 10, 2)->after('amount_type');
            }

            if (! Schema::hasColumn('coupons', 'min_qty')) {
                $table->unsignedInteger('min_qty')->nullable()->after('amount');
            }

            if (! Schema::hasColumn('coupons', 'max_qty')) {
                $table->unsignedInteger('max_qty')->nullable()->after('min_qty');
            }

            if (! Schema::hasColumn('coupons', 'min_cart_value')) {
                $table->decimal('min_cart_value', 10, 2)->default(0)->after('max_qty');
            }

            if (! Schema::hasColumn('coupons', 'max_cart_value')) {
                $table->decimal('max_cart_value', 10, 2)->nullable()->after('min_cart_value');
            }

            if (! Schema::hasColumn('coupons', 'usage_limit_per_user')) {
                $table->unsignedInteger('usage_limit_per_user')->default(0)->after('max_cart_value');
            }

            if (! Schema::hasColumn('coupons', 'total_usage_limit')) {
                $table->unsignedInteger('total_usage_limit')->default(0)->after('usage_limit_per_user');
            }

            if (! Schema::hasColumn('coupons', 'max_discount')) {
                $table->decimal('max_discount', 10, 2)->nullable()->after('total_usage_limit');
            }

            if (! Schema::hasColumn('coupons', 'used_count')) {
                $table->unsignedInteger('used_count')->default(0)->after('max_discount');
            }

            if (! Schema::hasColumn('coupons', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('used_count');
            }

            if (! Schema::hasColumn('coupons', 'status')) {
                $table->tinyInteger('status')->default(1)->after('expiry_date');
            }

            if (! Schema::hasColumn('coupons', 'visible')) {
                $table->tinyInteger('visible')->default(1)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left empty because the coupons table now owns these fields.
    }
};
