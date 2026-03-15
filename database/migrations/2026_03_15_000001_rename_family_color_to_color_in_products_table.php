<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('products', 'color')) {
            DB::statement('ALTER TABLE products DROP COLUMN color');
        }

        if (Schema::hasColumn('products', 'family_color')) {
            DB::statement('ALTER TABLE products DROP COLUMN family_color');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('products', 'color')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('color')->nullable();
            });
        }
    }
};
