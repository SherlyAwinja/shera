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
        if (!Schema::hasTable('filter_values')) {
            Schema::create('filter_values', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('filter_id');
                $table->string('value');
                $table->integer('sort')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
                $table->index('filter_id');
            });

            return;
        }

        $hasFilterId = Schema::hasColumn('filter_values', 'filter_id');
        $hasSort = Schema::hasColumn('filter_values', 'sort');
        $hasStatus = Schema::hasColumn('filter_values', 'status');
        $hasCreatedAt = Schema::hasColumn('filter_values', 'created_at');
        $hasUpdatedAt = Schema::hasColumn('filter_values', 'updated_at');

        Schema::table('filter_values', function (Blueprint $table) use (
            $hasFilterId,
            $hasSort,
            $hasStatus,
            $hasCreatedAt,
            $hasUpdatedAt
        ) {
            if (!$hasFilterId) {
                $table->unsignedBigInteger('filter_id')->nullable()->after('id');
                $table->index('filter_id');
            }

            if (!$hasSort) {
                $table->integer('sort')->default(0);
            }

            if (!$hasStatus) {
                $table->tinyInteger('status')->default(1);
            }

            if (!$hasCreatedAt && !$hasUpdatedAt) {
                $table->timestamps();
            } elseif (!$hasCreatedAt) {
                $table->timestamp('created_at')->nullable();
            } elseif (!$hasUpdatedAt) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filter_values');
    }
};
