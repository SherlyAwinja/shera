<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_logs')) {
            return;
        }

        if (! Schema::hasColumn('order_logs', 'tracking_link')) {
            Schema::table('order_logs', function (Blueprint $table) {
                $table->string('tracking_link', 1000)->nullable()->after('tracking_number');
            });
        }

        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('order_logs', 'tracking_link')) {
            DB::statement('ALTER TABLE `order_logs` MODIFY `tracking_link` VARCHAR(1000) NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_logs') || ! Schema::hasColumn('order_logs', 'tracking_link')) {
            return;
        }

        Schema::table('order_logs', function (Blueprint $table) {
            $table->dropColumn('tracking_link');
        });
    }
};
