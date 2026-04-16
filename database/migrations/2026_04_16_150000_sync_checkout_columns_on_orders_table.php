<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'order_uuid')) {
                $table->uuid('order_uuid')->nullable()->after('user_address_id');
            }

            if (! Schema::hasColumn('orders', 'address_label')) {
                $table->string('address_label')->nullable()->after('grand_total');
            }

            if (! Schema::hasColumn('orders', 'recipient_name')) {
                $table->string('recipient_name')->nullable()->after('address_label');
            }

            if (! Schema::hasColumn('orders', 'recipient_phone')) {
                $table->string('recipient_phone')->nullable()->after('recipient_name');
            }

            if (! Schema::hasColumn('orders', 'email')) {
                $table->string('email')->nullable()->after('recipient_phone');
            }

            if (! Schema::hasColumn('orders', 'country')) {
                $table->string('country')->nullable()->after('email');
            }

            if (! Schema::hasColumn('orders', 'county')) {
                $table->string('county')->nullable()->after('country');
            }

            if (! Schema::hasColumn('orders', 'sub_county')) {
                $table->string('sub_county')->nullable()->after('county');
            }

            if (! Schema::hasColumn('orders', 'address_line1')) {
                $table->string('address_line1')->nullable()->after('sub_county');
            }

            if (! Schema::hasColumn('orders', 'address_line2')) {
                $table->string('address_line2')->nullable()->after('address_line1');
            }

            if (! Schema::hasColumn('orders', 'estate')) {
                $table->string('estate')->nullable()->after('address_line2');
            }

            if (! Schema::hasColumn('orders', 'landmark')) {
                $table->text('landmark')->nullable()->after('estate');
            }

            if (! Schema::hasColumn('orders', 'pincode')) {
                $table->string('pincode', 20)->nullable()->after('landmark');
            }

            if (! Schema::hasColumn('orders', 'shipping_zone')) {
                $table->string('shipping_zone')->nullable()->after('pincode');
            }

            if (! Schema::hasColumn('orders', 'shipping_eta')) {
                $table->string('shipping_eta')->nullable()->after('shipping_zone');
            }

            if (! Schema::hasColumn('orders', 'shipping_quote')) {
                $table->json('shipping_quote')->nullable()->after('shipping_eta');
            }

            if (! Schema::hasColumn('orders', 'placed_at')) {
                $table->timestamp('placed_at')->nullable()->after('transaction_id');
            }
        });

        DB::table('orders')
            ->whereNull('placed_at')
            ->update([
                'placed_at' => DB::raw('created_at'),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $columns = [
                'order_uuid',
                'address_label',
                'recipient_name',
                'recipient_phone',
                'email',
                'country',
                'county',
                'sub_county',
                'address_line1',
                'address_line2',
                'estate',
                'landmark',
                'pincode',
                'shipping_zone',
                'shipping_eta',
                'shipping_quote',
                'placed_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
