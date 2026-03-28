<?php

namespace App\Http\Requests\Front\Concerns;

use App\Models\Country;
use App\Models\County;
use App\Models\SubCounty;
use Illuminate\Support\Str;

trait NormalizesAddressLocation
{
    protected function normalizeValue(mixed $value): ?string
    {
        $normalized = Str::of((string) $value)->squish()->toString();

        return $normalized === '' ? null : $normalized;
    }

    protected function normalizeCountry(mixed $value): string
    {
        $country = $this->normalizeValue($value) ?? 'Kenya';

        foreach ($this->countryOptions() as $option) {
            if (strcasecmp($option, $country) === 0) {
                return $option;
            }
        }

        return $country;
    }

    protected function normalizeKenyaCounty(mixed $value): ?string
    {
        $county = $this->normalizeValue($value);

        if ($county === null) {
            return null;
        }

        foreach ($this->kenyaCountyOptions() as $option) {
            if (strcasecmp($option, $county) === 0) {
                return $option;
            }
        }

        return $county;
    }

    protected function normalizeKenyaSubCounty(mixed $countyValue, mixed $subCountyValue): ?string
    {
        $subCounty = $this->normalizeValue($subCountyValue);

        if ($subCounty === null) {
            return null;
        }

        $county = $this->normalizeKenyaCounty($countyValue);

        foreach ($this->kenyaSubCountyOptions($county) as $option) {
            if (strcasecmp($option, $subCounty) === 0) {
                return $option;
            }
        }

        return $subCounty;
    }

    protected function shouldUseKenyaCounty(?string $country): bool
    {
        return strcasecmp((string) $country, 'Kenya') === 0;
    }

    protected function countryOptions(): array
    {
        $countries = Country::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        if (! empty($countries)) {
            return $countries;
        }

        return collect(config('locations.country_suggestions', []))
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
    }

    protected function kenyaCountyOptions(): array
    {
        $kenyaId = Country::query()
            ->whereRaw('LOWER(name) = ?', ['kenya'])
            ->value('id');

        $counties = $kenyaId
            ? County::query()
                ->where('country_id', $kenyaId)
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name')
                ->all()
            : [];

        if (! empty($counties)) {
            return $counties;
        }

        return config('locations.kenya_counties', []);
    }

    protected function kenyaSubCountyOptions(?string $county): array
    {
        $county = $this->normalizeKenyaCounty($county);

        if ($county === null) {
            return [];
        }

        $countyId = County::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($county)])
            ->value('id');

        $subCounties = $countyId
            ? SubCounty::query()
                ->where('county_id', $countyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name')
                ->all()
            : [];

        if (! empty($subCounties)) {
            return $subCounties;
        }

        return config('locations.kenya_sub_counties.' . $county, []);
    }
}
