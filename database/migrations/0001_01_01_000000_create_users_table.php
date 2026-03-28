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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            $table->string('user_type')
                ->default('Customer')
                ->comment('Customer, Vendor');

            $table->tinyInteger('status')
                ->unsigned()
                ->default(1)
                ->comment('1 = active, 0 = inactive');

            $table->string('address_line1')->nullable(); // main address
            $table->string('address_line2')->nullable(); // extra info

            $table->string('county')->nullable();       // Nairobi
            $table->string('sub_county')->nullable();   // Westlands
            $table->string('estate')->nullable();       // Kilimani

            $table->text('landmark')->nullable();       // "Opposite Quickmart"

            $table->string('country')->default('Kenya');


            $table->string('phone', 20)->nullable();
            $table->string('business_name')->nullable();

            $table->boolean('is_admin')
                ->default(false)
                ->comment('Flag for admin users');

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')
                ->nullable()
                ->index()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('users');
    }
};
