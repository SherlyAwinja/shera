<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (
            !Schema::hasColumn('products', 'gender')
            || !Schema::hasColumn('products', 'occasion')
            || !Schema::hasColumn('products', 'availability')
        ) {
            return;
        }

        DB::table('products')
            ->select('id', 'product_name', 'stock', 'gender', 'occasion', 'availability')
            ->orderBy('id')
            ->chunkById(200, function ($products) {
                foreach ($products as $product) {
                    $name = strtolower((string) ($product->product_name ?? ''));

                    $gender = trim((string) ($product->gender ?? ''));
                    if ($gender === '') {
                        if (str_contains($name, 'women')) {
                            $gender = 'women';
                        } elseif (str_contains($name, 'men')) {
                            $gender = 'men';
                        } elseif (str_contains($name, 'kids')) {
                            $gender = 'kids';
                        } else {
                            $gender = 'unisex';
                        }
                    }

                    $occasion = trim((string) ($product->occasion ?? ''));
                    if ($occasion === '') {
                        if (str_contains($name, 'gym')) {
                            $occasion = 'gym';
                        } elseif (str_contains($name, 'travel') || str_contains($name, 'duffle')) {
                            $occasion = 'travel';
                        } elseif (str_contains($name, 'work') || str_contains($name, 'office')) {
                            $occasion = 'work';
                        } else {
                            $occasion = 'cassual';
                        }
                    }

                    $availability = trim((string) ($product->availability ?? ''));
                    if ($availability === '') {
                        $availability = ((int) $product->stock) > 0 ? 'in_stock' : 'out_of_stock';
                    }

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update([
                            'gender' => strtolower($gender),
                            'occasion' => strtolower($occasion),
                            'availability' => strtolower($availability),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: this migration backfills existing business data.
    }
};
