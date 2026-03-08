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
        $hasAvailability = Schema::hasColumn('products', 'availability');
        $hasGender = Schema::hasColumn('products', 'gender');
        $hasOccasion = Schema::hasColumn('products', 'occasion');

        if ($hasAvailability && $hasGender && $hasOccasion) {
            return;
        }

        Schema::table('products', function (Blueprint $table) use ($hasAvailability, $hasGender, $hasOccasion) {
            if (!$hasAvailability) {
                $table->string('availability')->nullable()->after('stock');
            }

            if (!$hasGender) {
                $table->string('gender')->nullable()->after('strap_type');
            }

            if (!$hasOccasion) {
                $table->string('occasion')->nullable()->after('gender');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasAvailability = Schema::hasColumn('products', 'availability');
        $hasGender = Schema::hasColumn('products', 'gender');
        $hasOccasion = Schema::hasColumn('products', 'occasion');

        if (!$hasAvailability && !$hasGender && !$hasOccasion) {
            return;
        }

        Schema::table('products', function (Blueprint $table) use ($hasAvailability, $hasGender, $hasOccasion) {
            if ($hasOccasion) {
                $table->dropColumn('occasion');
            }

            if ($hasGender) {
                $table->dropColumn('gender');
            }

            if ($hasAvailability) {
                $table->dropColumn('availability');
            }
        });
    }
};
