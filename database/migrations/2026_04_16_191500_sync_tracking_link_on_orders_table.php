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

        if (! Schema::hasColumn('orders', 'tracking_link')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('tracking_link', 1000)->nullable()->after('tracking_number');
            });
        }

        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('orders', 'tracking_link')) {
            DB::statement('ALTER TABLE `orders` MODIFY `tracking_link` VARCHAR(1000) NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'tracking_link')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('tracking_link');
        });
    }
};
