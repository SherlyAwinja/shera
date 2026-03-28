<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('user_addresses', 'full_name')) {
                $table->string('full_name')->nullable()->after('label');
            }

            if (! Schema::hasColumn('user_addresses', 'phone')) {
                $table->string('phone', 30)->nullable()->after('full_name');
            }

            if (! Schema::hasColumn('user_addresses', 'pincode')) {
                $table->string('pincode', 20)->nullable()->after('landmark');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            if (Schema::hasColumn('user_addresses', 'pincode')) {
                $table->dropColumn('pincode');
            }

            if (Schema::hasColumn('user_addresses', 'phone')) {
                $table->dropColumn('phone');
            }

            if (Schema::hasColumn('user_addresses', 'full_name')) {
                $table->dropColumn('full_name');
            }
        });
    }
};
