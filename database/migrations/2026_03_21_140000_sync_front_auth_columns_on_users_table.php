<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bring older user tables in line with the current auth/profile model.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'user_type')) {
                $table->string('user_type')->default('Customer');
            }

            if (!Schema::hasColumn('users', 'status')) {
                $table->tinyInteger('status')->unsigned()->default(1);
            }

            if (!Schema::hasColumn('users', 'address_line1')) {
                $table->string('address_line1')->nullable();
            }

            if (!Schema::hasColumn('users', 'address_line2')) {
                $table->string('address_line2')->nullable();
            }

            if (!Schema::hasColumn('users', 'county')) {
                $table->string('county')->nullable();
            }

            if (!Schema::hasColumn('users', 'sub_county')) {
                $table->string('sub_county')->nullable();
            }

            if (!Schema::hasColumn('users', 'estate')) {
                $table->string('estate')->nullable();
            }

            if (!Schema::hasColumn('users', 'landmark')) {
                $table->text('landmark')->nullable();
            }

            if (!Schema::hasColumn('users', 'country')) {
                $table->string('country')->default('Kenya');
            }

            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable();
            }

            if (!Schema::hasColumn('users', 'business_name')) {
                $table->string('business_name')->nullable();
            }

            if (!Schema::hasColumn('users', 'is_admin')) {
                $table->boolean('is_admin')->default(false);
            }

            if (!Schema::hasColumn('users', 'remember_token')) {
                $table->rememberToken();
            }
        });
    }

    public function down(): void
    {
        // Intentionally left empty: this migration only syncs legacy schemas.
    }
};
