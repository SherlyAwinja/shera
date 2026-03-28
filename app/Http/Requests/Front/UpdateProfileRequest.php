<?php

namespace App\Http\Requests\Front;

use App\Http\Requests\Front\Concerns\NormalizesAddressLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    use NormalizesAddressLocation;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
                Rule::unique('users', 'pending_email')->ignore($this->user()?->id),
            ],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+()\\-\\s]{7,20}$/'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:191', Rule::in($this->countryOptions())],
            'county' => ['nullable', 'string', 'max:191'],
            'sub_county' => ['nullable', 'string', 'max:191'],
            'estate' => ['nullable', 'string', 'max:191'],
            'landmark' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $country = $this->normalizeCountry($this->input('country'));

        $this->merge([
            'name' => $this->normalizeValue($this->input('name')),
            'email' => Str::lower($this->normalizeValue($this->input('email')) ?? ''),
            'phone' => $this->normalizeValue($this->input('phone')),
            'address_line1' => $this->normalizeValue($this->input('address_line1')),
            'address_line2' => $this->normalizeValue($this->input('address_line2')),
            'country' => $country,
            'county' => $this->shouldUseKenyaCounty($country)
                ? $this->normalizeKenyaCounty($this->input('county'))
                : $this->normalizeValue($this->input('county')),
            'sub_county' => $this->shouldUseKenyaCounty($country)
                ? $this->normalizeKenyaSubCounty($this->input('county'), $this->input('sub_county'))
                : $this->normalizeValue($this->input('sub_county')),
            'estate' => $this->normalizeValue($this->input('estate')),
            'landmark' => $this->normalizeValue($this->input('landmark')),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->shouldUseKenyaCounty($this->input('country'))) {
                return;
            }

            $county = $this->input('county');

            if (blank($county)) {
                $validator->errors()->add('county', 'Please select a county when Kenya is selected.');
                return;
            }

            if (! in_array($county, $this->kenyaCountyOptions(), true)) {
                $validator->errors()->add('county', 'Please choose a valid Kenyan county.');
                return;
            }

            $subCounty = $this->input('sub_county');

            if (blank($subCounty)) {
                $validator->errors()->add('sub_county', 'Please select a sub-county when Kenya is selected.');
                return;
            }

            if (! in_array($subCounty, $this->kenyaSubCountyOptions($county), true)) {
                $validator->errors()->add('sub_county', 'Please choose a valid sub-county for the selected county.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'country.in' => 'Please choose a valid country.',
            'phone.regex' => 'Please enter a valid phone number.',
        ];
    }
}
