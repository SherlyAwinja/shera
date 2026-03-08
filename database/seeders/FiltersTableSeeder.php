<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Filter;
use App\Models\FilterValue;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class FiltersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->backfillProductFilterColumns();

            $bagTypeFilter = Filter::updateOrCreate(
                ['filter_name' => 'Bag Type'],
                ['sort' => 1, 'status' => 1, 'filter_column' => 'bag_type']
            );

            $strapTypeFilter = Filter::updateOrCreate(
                ['filter_name' => 'Strap Type'],
                ['sort' => 2, 'status' => 1, 'filter_column' => 'strap_type']
            );

            $bagTypeValues = $this->buildFilterValuesFromProducts('bag_type', ['Tote', 'Crossbody', 'Duffel', 'Laptop', 'Toiletry', 'Cosmetic', 'Clutch', 'Satchel', 'Backpack', 'Hobo', 'Wallet', 'Sling', 'Shoulder', 'Messenger', 'Briefcase', 'Bucket', 'Fanny Pack', 'Diaper', 'Luggage', 'Weekender', 'Doctor', 'Baguette', 'Pouch', 'Organizer', 'Saddle', 'Barrel', 'Box', 'Envelope', 'Drawstring', 'Trapeze', 'Frame', 'Minaudiere', 'Wristlet']);
            $strapTypeValues = $this->buildFilterValuesFromProducts('strap_type', ['Chain', 'Leather', 'Fabric', 'Wooden', 'Metal', 'Braided','Plastic','Webbing','Soft cord handles', 'Top handle', 'Adjustable strap', 'Detachable strap', 'Shoulder strap', 'Crossbody strap', 'Belt strap']);

            $bagTypeValueMap = $this->syncFilterValues((int) $bagTypeFilter->id, $bagTypeValues);
            $strapTypeValueMap = $this->syncFilterValues((int) $strapTypeFilter->id, $strapTypeValues);

            $this->syncProductFilterLinks((int) $bagTypeFilter->id, 'bag_type', $bagTypeValueMap);
            $this->syncProductFilterLinks((int) $strapTypeFilter->id, 'strap_type', $strapTypeValueMap);
        });
    }

    private function backfillProductFilterColumns(): void
    {
        $products = Product::query()
            ->select('id', 'product_name', 'material', 'bag_type', 'strap_type')
            ->get();

        foreach ($products as $product) {
            $updates = [];

            if ($this->isBlank($product->bag_type)) {
                $inferredBagType = $this->inferBagType((string) $product->product_name);
                if ($inferredBagType !== null) {
                    $updates['bag_type'] = $inferredBagType;
                }
            }

            if ($this->isBlank($product->strap_type)) {
                $inferredStrapType = $this->inferStrapType(
                    (string) $product->product_name,
                    (string) ($product->material ?? '')
                );
                if ($inferredStrapType !== null) {
                    $updates['strap_type'] = $inferredStrapType;
                }
            }

            if (!empty($updates)) {
                Product::query()
                    ->whereKey($product->id)
                    ->update($updates);
            }
        }
    }

    private function buildFilterValuesFromProducts(string $column, array $defaults): array
    {
        $values = [];

        foreach ($defaults as $defaultValue) {
            $formattedDefault = $this->formatFilterValue((string) $defaultValue);
            if ($formattedDefault !== '') {
                $values[] = $formattedDefault;
            }
        }

        $rawColumnValues = Product::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->pluck($column)
            ->all();

        foreach ($rawColumnValues as $rawColumnValue) {
            foreach ($this->splitFilterValues((string) $rawColumnValue) as $rawValue) {
                $formattedValue = $this->formatFilterValue($rawValue);
                if ($formattedValue !== '') {
                    $values[] = $formattedValue;
                }
            }
        }

        $uniqueValues = [];
        $seen = [];

        foreach ($values as $value) {
            $key = strtolower($value);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $uniqueValues[] = $value;
        }

        return $uniqueValues;
    }

    private function syncFilterValues(int $filterId, array $values): array
    {
        $existingValues = FilterValue::query()
            ->where('filter_id', $filterId)
            ->get();

        $existingMap = [];
        foreach ($existingValues as $existingValue) {
            $existingMap[strtolower(trim((string) $existingValue->value))] = $existingValue;
        }

        $valueIdMap = [];
        foreach ($values as $index => $value) {
            $normalizedKey = strtolower(trim($value));

            if (isset($existingMap[$normalizedKey])) {
                $record = $existingMap[$normalizedKey];
                $record->update([
                    'value' => $value,
                    'sort' => $index + 1,
                    'status' => 1,
                ]);
            } else {
                $record = FilterValue::create([
                    'filter_id' => $filterId,
                    'value' => $value,
                    'sort' => $index + 1,
                    'status' => 1,
                ]);
            }

            $valueIdMap[$normalizedKey] = (int) $record->id;
        }

        return $valueIdMap;
    }

    private function syncProductFilterLinks(int $filterId, string $productColumn, array $valueIdMap): void
    {
        $filterValueIds = FilterValue::query()
            ->where('filter_id', $filterId)
            ->pluck('id')
            ->toArray();

        if (!empty($filterValueIds)) {
            DB::table('product_filter_values')
                ->whereIn('filter_value_id', $filterValueIds)
                ->delete();
        }

        if (empty($valueIdMap)) {
            return;
        }

        $products = Product::query()
            ->select('id', $productColumn)
            ->whereNotNull($productColumn)
            ->where($productColumn, '!=', '')
            ->get();

        $rows = [];
        $now = now();

        foreach ($products as $product) {
            foreach ($this->splitFilterValues((string) $product->{$productColumn}) as $rawValue) {
                $normalizedValue = strtolower($this->formatFilterValue($rawValue));
                if ($normalizedValue === '' || !isset($valueIdMap[$normalizedValue])) {
                    continue;
                }

                $dedupeKey = (int) $product->id . ':' . (int) $valueIdMap[$normalizedValue];
                $rows[$dedupeKey] = [
                    'product_id' => (int) $product->id,
                    'filter_value_id' => (int) $valueIdMap[$normalizedValue],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($rows)) {
            DB::table('product_filter_values')->insert(array_values($rows));
        }
    }

    private function splitFilterValues(string $rawValue): array
    {
        $parts = preg_split('/[;,|]/', $rawValue);
        if ($parts === false) {
            return [];
        }

        return array_values(array_filter(array_map('trim', $parts), fn ($value) => $value !== ''));
    }

    private function formatFilterValue(string $value): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($value));
        if ($normalized === null || $normalized === '') {
            return '';
        }

        return ucwords(strtolower($normalized));
    }

    private function isBlank($value): bool
    {
        return trim((string) $value) === '';
    }

    private function inferBagType(string $productName): ?string
    {
        $name = strtolower($productName);
        $map = [
            'crossbody' => 'Crossbody',
            'tote' => 'Tote',
            'duffle' => 'Duffle',
            'hobo' => 'Hobo',
            'wallet' => 'Wallet',
            'satchel' => 'Satchel',
            'backpack' => 'Backpack',
            'clutch' => 'Clutch',
            'laptop' => 'Laptop',
            'toiletry' => 'Toiletry',
            'cosmetic' => 'Cosmetic',
            'sling' => 'Sling',
            'shoulder' => 'Shoulder',
            'messenger' => 'Messenger',
            'briefcase' => 'Briefcase',
            'bucket' => 'Bucket',
            'fanny' => 'Fanny Pack',
            'diaper' => 'Diaper',
            'luggage' => 'Luggage',
            'weekender' => 'Weekender',
            'doctor' => 'Doctor',
            'baguette' => 'Baguette',
            'pouch' => 'Pouch',
            'organizer' => 'Organizer',
            'saddle' => 'Saddle',
            'barrel' => 'Barrel',
            'box' => 'Box',
            'envelope' => 'Envelope',
            'drawstring' => 'Drawstring',
            'trapeze' => 'Trapeze',
            'frame' => 'Frame',
            'minaudiere' => 'Minaudiere',
            'wristlet' => 'Wristlet',
        ];

        foreach ($map as $keyword => $value) {
            if (str_contains($name, $keyword)) {
                return $value;
            }
        }

        return null;
    }

    private function inferStrapType(string $productName, string $material): ?string
    {
        $haystack = strtolower($productName . ' ' . $material);

        if (str_contains($haystack, 'chain')) {
            return 'Chain';
        }

        if (str_contains($haystack, 'leather')) {
            return 'Leather';
        }
        if (str_contains($haystack, 'fabric')) {
            return 'Fabric';
        }
        if (str_contains($haystack, 'wood')) {
            return 'Wooden';
        }
        if (str_contains($haystack, 'metal')) {
            return 'Metal';
        }
        if (str_contains($haystack, 'braid')) {
            return 'Braided';
        }
        if (str_contains($haystack, 'plastic')) {
            return 'Plastic';
        }
        if (str_contains($haystack, 'webbing')) {
            return 'Webbing';
        }
        if (str_contains($haystack, 'soft cord')) {
            return 'Soft cord handles';
        }
        if (str_contains($haystack, 'top handle')) {
            return 'Top handle';
        }
        if (str_contains($haystack, 'adjustable')) {
            return 'Adjustable strap';
        }
        if (str_contains($haystack, 'detachable')) {
            return 'Detachable strap';
        }
        if (str_contains($haystack, 'shoulder')) {
            return 'Shoulder strap';
        }        if (str_contains($haystack, 'crossbody')) {
            return 'Crossbody strap';
        }        if (str_contains($haystack, 'belt')) {
            return 'Belt strap';
        }

        return null;
    }
}
