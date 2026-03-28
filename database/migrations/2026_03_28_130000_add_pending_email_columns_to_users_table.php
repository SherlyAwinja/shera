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
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'pending_email')) {
                $table->string('pending_email')->nullable()->unique();
            }

            if (! Schema::hasColumn('users', 'email_change_requested_at')) {
                $table->timestamp('email_change_requested_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'pending_email')) {
                $table->dropUnique('users_pending_email_unique');
                $table->dropColumn('pending_email');
            }

            if (Schema::hasColumn('users', 'email_change_requested_at')) {
                $table->dropColumn('email_change_requested_at');
            }
        });
    }
};
