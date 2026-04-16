@php
    $initialVariantRows = old('variants');
    $legacySeedMode = null;

    if (is_null($initialVariantRows)) {
        $initialVariantRows = isset($product)
            ? $product->productVariants
                ->map(fn ($variant) => [
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'stock' => $variant->stock,
                ])
                ->all()
            : [];

        if (empty($initialVariantRows) && isset($product)) {
            $legacyColors = collect(preg_split('/\s*,\s*/', (string) ($product->product_color ?? ''), -1, PREG_SPLIT_NO_EMPTY))
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values();
            $legacyAttributes = collect($product->attributes ?? []);
            $legacySizes = $legacyAttributes
                ->pluck('size')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values();
            $legacyColorStock = collect(is_array($product->color_stock ?? null) ? $product->color_stock : []);

            if ($legacySizes->count() === 1 && $legacyColors->isNotEmpty()) {
                $singleSize = $legacySizes->first();
                $initialVariantRows = $legacyColors
                    ->map(function ($color) use ($singleSize, $legacyColorStock) {
                        $matchedStock = $legacyColorStock
                            ->first(function ($stock, $label) use ($color) {
                                return strtolower(trim((string) $label)) === strtolower(trim((string) $color));
                            });

                        return [
                            'size' => $singleSize,
                            'color' => $color,
                            'stock' => max(0, (int) $matchedStock),
                        ];
                    })
                    ->all();
                $legacySeedMode = 'single_size_color_stock';
            } elseif ($legacyColors->count() === 1 && $legacySizes->isNotEmpty()) {
                $singleColor = $legacyColors->first();
                $initialVariantRows = $legacyAttributes
                    ->map(fn ($attribute) => [
                        'size' => trim((string) $attribute->size),
                        'color' => $singleColor,
                        'stock' => max(0, (int) $attribute->stock),
                    ])
                    ->all();
                $legacySeedMode = 'single_color_size_stock';
            } elseif ($legacyColors->isNotEmpty() && $legacySizes->isNotEmpty()) {
                $initialVariantRows = [];

                foreach ($legacySizes as $size) {
                    foreach ($legacyColors as $color) {
                        $initialVariantRows[] = [
                            'size' => $size,
                            'color' => $color,
                            'stock' => 0,
                        ];
                    }
                }

                $legacySeedMode = 'legacy_matrix_review_required';
            }
        }
    }

    $variantRows = collect($initialVariantRows)
        ->map(fn ($variant) => [
            'size' => trim((string) data_get($variant, 'size')),
            'color' => trim((string) data_get($variant, 'color')),
            'stock' => max(0, (int) data_get($variant, 'stock', 0)),
        ])
        ->filter(fn ($variant) => $variant['size'] !== '' || $variant['color'] !== '' || $variant['stock'] > 0)
        ->values();

    if ($variantRows->isEmpty()) {
        $variantRows = collect([[
            'size' => '',
            'color' => '',
            'stock' => 0,
        ]]);
    }

    $variantSizeSelections = $variantRows->pluck('size')->filter()->unique()->values();
    $knownColors = collect($productColors)->pluck('name')->map(fn ($value) => trim((string) $value))->filter()->unique()->values();
    $variantColorSelections = $variantRows->pluck('color')->filter()->unique()->values();
    $generatorColorOptions = $knownColors->merge($variantColorSelections)->unique()->values();
@endphp

<style>
    .variant-panel {
        border: 1px solid rgba(37, 99, 235, 0.12);
        border-radius: 20px;
        background:
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.10), transparent 34%),
            linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
        padding: 1.5rem;
        box-shadow: 0 18px 40px -32px rgba(15, 23, 42, 0.55);
    }

    .variant-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1.25rem;
    }

    .variant-panel-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #132238;
        margin-bottom: 0.25rem;
    }

    .variant-panel-copy {
        color: #5c6b7c;
        margin: 0;
        max-width: 640px;
    }

    .variant-summary-pill {
        border-radius: 999px;
        padding: 0.55rem 0.95rem;
        background: #132238;
        color: #ffffff;
        font-size: 0.9rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .variant-generator-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .variant-generator-card {
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.92);
        padding: 1rem;
    }

    .variant-generator-label {
        display: block;
        font-weight: 600;
        color: #132238;
        margin-bottom: 0.45rem;
    }

    .variant-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .variant-toolbar-actions {
        display: flex;
        gap: 0.65rem;
        flex-wrap: wrap;
    }

    .variant-stock-note {
        color: #5c6b7c;
        font-size: 0.9rem;
        margin: 0;
    }

    .variant-table-shell {
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 18px;
        background: #ffffff;
        overflow: hidden;
    }

    .variant-table {
        margin-bottom: 0;
    }

    .variant-table thead th {
        background: #f3f7fb;
        color: #3f5165;
        border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        font-size: 0.82rem;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        padding: 0.9rem 1rem;
    }

    .variant-table tbody td {
        padding: 0.9rem 1rem;
        vertical-align: middle;
        border-top: 1px solid rgba(226, 232, 240, 0.7);
    }

    .variant-table .form-control {
        border-radius: 12px;
        min-height: 44px;
    }

    .variant-action-btn {
        min-width: 42px;
        min-height: 42px;
        border-radius: 12px;
    }

    .variant-legacy-alert {
        border-radius: 14px;
        margin-bottom: 1rem;
    }

    @media (max-width: 767.98px) {
        .variant-panel {
            padding: 1rem;
        }

        .variant-table thead th,
        .variant-table tbody td {
            padding: 0.75rem;
        }
    }
</style>

<div
    id="product-variant-manager"
    class="variant-panel"
    data-initial-variants='@json($variantRows->all())'
>
    <div class="variant-panel-header">
        <div>
            <h4 class="variant-panel-title mb-0">Product Variants</h4>
            <p class="variant-panel-copy">Track stock by exact size and color combinations so storefront availability, totals, and admin inventory all read from the same source.</p>
        </div>
        <div class="variant-summary-pill" id="variant-summary-pill">0 variants | 0 in stock</div>
    </div>

    @if($legacySeedMode === 'legacy_matrix_review_required')
        <div class="alert alert-warning variant-legacy-alert mb-3">
            Legacy size and color values were converted into draft combinations. Review each stock quantity before saving because the previous split stock model could not map totals exactly.
        </div>
    @elseif($legacySeedMode)
        <div class="alert alert-info variant-legacy-alert mb-3">
            Existing stock was prefilled from the previous product setup. Review the combinations and save once to switch this product fully to variant-based stock.
        </div>
    @endif

    <div class="variant-generator-grid">
        <div class="variant-generator-card">
            <label class="variant-generator-label" for="variant-size-pool">Sizes</label>
            <select id="variant-size-pool" class="form-control select2-tags" multiple data-placeholder="Add sizes for bulk generation">
                @foreach($variantSizeSelections as $size)
                    <option value="{{ $size }}" selected>{{ $size }}</option>
                @endforeach
            </select>
            <small class="text-muted d-block mt-2">Select or type sizes such as `S`, `M`, `L`, or `One Size`.</small>
        </div>

        <div class="variant-generator-card">
            <label class="variant-generator-label" for="variant-color-pool">Colors</label>
            <select id="variant-color-pool" class="form-control select2-tags" multiple data-placeholder="Add colors for bulk generation">
                @foreach($generatorColorOptions as $color)
                    <option value="{{ $color }}" {{ $variantColorSelections->contains($color) ? 'selected' : '' }}>{{ $color }}</option>
                @endforeach
            </select>
            <small class="text-muted d-block mt-2">Select from your saved palette or type a new color name.</small>
        </div>
    </div>

    <div class="variant-toolbar">
        <div class="variant-toolbar-actions">
            <button type="button" class="btn btn-primary" id="generate-variant-combinations">
                <i class="fas fa-bolt me-1"></i> Generate combinations
            </button>
            <button type="button" class="btn btn-outline-primary" id="add-variant-row">
                <i class="fas fa-plus me-1"></i> Add Variant
            </button>
        </div>
        <p class="variant-stock-note" id="variant-total-stock-note">Total stock updates automatically from the rows below.</p>
    </div>

    <div class="table-responsive variant-table-shell">
        <table class="table variant-table align-middle">
            <thead>
                <tr>
                    <th style="width: 32%">Size</th>
                    <th style="width: 32%">Color</th>
                    <th style="width: 22%">Quantity</th>
                    <th class="text-end" style="width: 14%">Action</th>
                </tr>
            </thead>
            <tbody id="product-variants-body"></tbody>
        </table>
    </div>

    <small class="text-muted d-block mt-3">Each saved row becomes a record in `product_variants` and the product `stock` total is recalculated from these rows.</small>
</div>

<template id="product-variant-row-template">
    <tr class="product-variant-row" data-row-index="__INDEX__">
        <td>
            <input
                type="text"
                class="form-control"
                name="variants[__INDEX__][size]"
                data-field="size"
                list="variant-size-datalist"
                placeholder="e.g. M"
            >
        </td>
        <td>
            <input
                type="text"
                class="form-control"
                name="variants[__INDEX__][color]"
                data-field="color"
                list="variant-color-datalist"
                placeholder="e.g. Black"
            >
        </td>
        <td>
            <input
                type="number"
                min="0"
                step="1"
                class="form-control"
                name="variants[__INDEX__][stock]"
                data-field="stock"
                value="0"
                placeholder="0"
            >
        </td>
        <td class="text-end">
            <button type="button" class="btn btn-outline-danger variant-action-btn remove-variant-row" aria-label="Remove variant">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

<datalist id="variant-size-datalist">
    @foreach($variantSizeSelections as $size)
        <option value="{{ $size }}"></option>
    @endforeach
</datalist>

<datalist id="variant-color-datalist">
    @foreach($generatorColorOptions as $color)
        <option value="{{ $color }}"></option>
    @endforeach
</datalist>
