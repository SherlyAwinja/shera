<?php

namespace App\Http\Requests\Front;

use App\Http\Requests\Front\Concerns\NormalizesAddressLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertUserAddressRequest extends FormRequest
{
    use NormalizesAddressLocation;

    protected $errorBag = 'addressBook';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'editing_address_id' => ['nullable', 'integer'],
            'saved_address_label' => ['required', 'string', 'max:100'],
            'saved_address_full_name' => ['nullable', 'string', 'max:150'],
            'saved_address_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'saved_address_country' => ['required', 'string', 'max:191', Rule::in($this->countryOptions())],
            'saved_address_county' => ['nullable', 'string', 'max:191'],
            'saved_address_sub_county' => ['nullable', 'string', 'max:191'],
            'saved_address_line1' => ['required', 'string', 'max:255'],
            'saved_address_line2' => ['nullable', 'string', 'max:255'],
            'saved_address_estate' => ['nullable', 'string', 'max:191'],
            'saved_address_landmark' => ['nullable', 'string', 'max:1000'],
            'saved_address_pincode' => ['nullable', 'string', 'regex:/^[0-9]{4,10}$/'],
            'saved_address_make_default' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $country = $this->normalizeCountry($this->input('saved_address_country'));

        $this->merge([
            'saved_address_label' => $this->normalizeValue($this->input('saved_address_label')),
            'saved_address_full_name' => $this->normalizeValue($this->input('saved_address_full_name')),
            'saved_address_phone' => $this->normalizeValue($this->input('saved_address_phone')),
            'saved_address_country' => $country,
            'saved_address_county' => $this->shouldUseKenyaCounty($country)
                ? $this->normalizeKenyaCounty($this->input('saved_address_county'))
                : $this->normalizeValue($this->input('saved_address_county')),
            'saved_address_sub_county' => $this->shouldUseKenyaCounty($country)
                ? $this->normalizeKenyaSubCounty($this->input('saved_address_county'), $this->input('saved_address_sub_county'))
                : $this->normalizeValue($this->input('saved_address_sub_county')),
            'saved_address_line1' => $this->normalizeValue($this->input('saved_address_line1')),
            'saved_address_line2' => $this->normalizeValue($this->input('saved_address_line2')),
            'saved_address_estate' => $this->normalizeValue($this->input('saved_address_estate')),
            'saved_address_landmark' => $this->normalizeValue($this->input('saved_address_landmark')),
            'saved_address_pincode' => preg_replace('/\D+/', '', (string) $this->input('saved_address_pincode')),
            'saved_address_make_default' => $this->boolean('saved_address_make_default'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $country = $this->input('saved_address_country');
            $county = $this->input('saved_address_county');
            $subCounty = $this->input('saved_address_sub_county');

            if (blank($county)) {
                $validator->errors()->add(
                    'saved_address_county',
                    $this->shouldUseKenyaCounty($country)
                        ? 'Please select a county when Kenya is selected.'
                        : 'Please enter a state, province, county, or region.'
                );

                return;
            }

            if (blank($subCounty)) {
                $validator->errors()->add(
                    'saved_address_sub_county',
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
                $validator->errors()->add('saved_address_county', 'Please choose a valid Kenyan county.');
                return;
            }

            if (! in_array($subCounty, $this->kenyaSubCountyOptions($county), true)) {
                $validator->errors()->add('saved_address_sub_county', 'Please choose a valid sub-county for the selected county.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'saved_address_country.in' => 'Please choose a valid country.',
            'saved_address_phone.regex' => 'Use only numbers, spaces, brackets, or a leading plus sign for the phone number.',
            'saved_address_pincode.regex' => 'Enter a valid pincode using 4 to 10 digits.',
        ];
    }

    public function addressAttributes(): array
    {
        return [
            'label' => $this->validated()['saved_address_label'],
            'full_name' => $this->validated()['saved_address_full_name'] ?? null,
            'phone' => $this->validated()['saved_address_phone'] ?? null,
            'country' => $this->validated()['saved_address_country'],
            'county' => $this->validated()['saved_address_county'],
            'sub_county' => $this->validated()['saved_address_sub_county'],
            'address_line1' => $this->validated()['saved_address_line1'],
            'address_line2' => $this->validated()['saved_address_line2'] ?? null,
            'estate' => $this->validated()['saved_address_estate'] ?? null,
            'landmark' => $this->validated()['saved_address_landmark'] ?? null,
            'pincode' => $this->validated()['saved_address_pincode'] ?? null,
        ];
    }

    public function shouldMakeDefault(): bool
    {
        return (bool) ($this->validated()['saved_address_make_default'] ?? false);
    }
}
