<?php

namespace App\Http\Requests\Front;

use App\Http\Requests\Front\Concerns\NormalizesAddressLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CheckoutAddressRequest extends FormRequest
{
    use NormalizesAddressLocation;

    protected $errorBag = 'checkoutAddress';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'address_country' => ['required', 'string', 'max:191', Rule::in($this->countryOptions())],
            'address_county' => ['nullable', 'string', 'max:191'],
            'address_sub_county' => ['nullable', 'string', 'max:191'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'address_estate' => ['nullable', 'string', 'max:191'],
            'address_landmark' => ['nullable', 'string', 'max:1000'],
            'address_pincode' => ['required', 'string', 'regex:/^[0-9]{4,10}$/'],
            'make_default' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $country = $this->normalizeCountry($this->input('address_country'));

        $this->merge([
            'full_name' => $this->normalizeValue($this->input('full_name')),
            'phone' => $this->normalizeValue($this->input('phone')),
            'address_country' => $country,
            'address_county' => $this->shouldUseKenyaCounty($country)
                ? $this->normalizeKenyaCounty($this->input('address_county'))
                : $this->normalizeValue($this->input('address_county')),
            'address_sub_county' => $this->shouldUseKenyaCounty($country)
                ? $this->normalizeKenyaSubCounty($this->input('address_county'), $this->input('address_sub_county'))
                : $this->normalizeValue($this->input('address_sub_county')),
            'address_line1' => $this->normalizeValue($this->input('address_line1')),
            'address_line2' => $this->normalizeValue($this->input('address_line2')),
            'address_estate' => $this->normalizeValue($this->input('address_estate')),
            'address_landmark' => $this->normalizeValue($this->input('address_landmark')),
            'address_pincode' => preg_replace('/\D+/', '', (string) $this->input('address_pincode')),
            'make_default' => $this->boolean('make_default'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $country = $this->input('address_country');
            $county = $this->input('address_county');
            $subCounty = $this->input('address_sub_county');

            if (blank($county)) {
                $validator->errors()->add(
                    'address_county',
                    $this->shouldUseKenyaCounty($country)
                        ? 'Please select a county when Kenya is selected.'
                        : 'Please enter a state, province, county, or region.'
                );

                return;
            }

            if (blank($subCounty)) {
                $validator->errors()->add(
                    'address_sub_county',
                    $this->shouldUseKenyaCounty($country)
                        ? 'Please select a sub-county when Kenya is selected.'
                        : 'Please enter a city, district, or area.'
                );

                return;
            }

            if (! $this->shouldUseKenyaCounty($country)) {
                return;
            }

            if (! in_array($county, $this->kenyaCountyOptions(), true)) {
                $validator->errors()->add('address_county', 'Please choose a valid Kenyan county.');
                return;
            }

            if (! in_array($subCounty, $this->kenyaSubCountyOptions($county), true)) {
                $validator->errors()->add('address_sub_county', 'Please choose a valid sub-county for the selected county.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Use only numbers, spaces, brackets, or a leading plus sign for the phone number.',
            'address_country.in' => 'Please choose a valid country.',
            'address_pincode.regex' => 'Enter a valid pincode using 4 to 10 digits.',
        ];
    }

    public function addressAttributes(): array
    {
        $validated = $this->validated();

        return [
            'label' => $this->buildAddressLabel($validated),
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'country' => $validated['address_country'],
            'county' => $validated['address_county'],
            'sub_county' => $validated['address_sub_county'],
            'address_line1' => $validated['address_line1'],
            'address_line2' => $validated['address_line2'] ?? null,
            'estate' => $validated['address_estate'] ?? null,
            'landmark' => $validated['address_landmark'] ?? null,
            'pincode' => $validated['address_pincode'],
        ];
    }

    public function shouldMakeDefault(): bool
    {
        return (bool) ($this->validated()['make_default'] ?? false);
    }

    protected function buildAddressLabel(array $validated): string
    {
        $label = collect([
            $validated['full_name'] ?? null,
            $validated['address_estate'] ?? null,
            $validated['address_sub_county'] ?? null,
        ])->filter()->implode(' - ');

        $label = Str::limit($label, 100, '');

        return $label !== '' ? $label : 'Saved Address';
    }
}
