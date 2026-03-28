@php
    $addresses = collect($addresses ?? []);
@endphp

@if ($addresses->isEmpty())
    <div class="checkout-address-empty">
        No saved delivery addresses yet. Add one below to unlock shipping calculation and payment readiness.
    </div>
@else
    <div class="checkout-address-grid">
        @foreach ($addresses as $address)
            @php
                $isSelected = (int) $selectedAddressId === (int) $address->id;
                $missingBits = collect([
                    blank($address->recipient_phone) ? 'phone missing' : null,
                    blank($address->pincode) ? 'pincode missing' : null,
                ])->filter()->values();
            @endphp

            <div class="checkout-address-card {{ $isSelected ? 'is-selected' : '' }} {{ $address->is_default ? 'is-default' : '' }}"
                 data-address-card
                 data-address-id="{{ $address->id }}">
                <div class="checkout-address-head">
                    <div>
                        <div class="checkout-address-label">{{ $address->label }}</div>
                        <div class="checkout-address-recipient">
                            <strong>{{ $address->recipient_name ?: 'Recipient not set' }}</strong>
                            @if ($address->recipient_phone)
                                <span class="d-block">{{ $address->recipient_phone }}</span>
                            @endif
                        </div>
                    </div>

                    @if ($address->is_default)
                        <span class="checkout-badge" data-tone="default">Default</span>
                    @endif
                </div>

                <div class="checkout-badge-row">
                    @if ($address->pincode)
                        <span class="checkout-badge" data-tone="default">Pincode {{ $address->pincode }}</span>
                    @endif

                    @foreach ($missingBits as $bit)
                        <span class="checkout-badge" data-tone="missing">{{ $bit }}</span>
                    @endforeach
                </div>

                <div class="checkout-address-copy">{{ $address->full_address ?: 'Address details are incomplete.' }}</div>

                <div class="checkout-address-actions">
                    <button type="button"
                            class="btn {{ $isSelected ? 'btn-primary' : 'btn-outline-primary' }} checkout-address-select"
                            data-address-select
                            data-select-url="{{ route('user.checkout.addresses.select', ['address' => $address->id], false) }}"
                            aria-pressed="{{ $isSelected ? 'true' : 'false' }}">
                        {{ $isSelected ? 'Selected for payment' : 'Use this address' }}
                    </button>

                    <button type="button"
                            class="btn btn-outline-secondary checkout-address-action"
                            data-address-edit
                            data-address-id="{{ $address->id }}"
                            data-update-url="{{ route('user.checkout.addresses.update', ['address' => $address->id], false) }}"
                            data-address-label="{{ $address->label }}"
                            data-address-full-name="{{ $address->full_name ?: $address->recipient_name }}"
                            data-address-phone="{{ $address->phone ?: $address->recipient_phone }}"
                            data-address-country="{{ $address->country ?: 'Kenya' }}"
                            data-address-county="{{ $address->county }}"
                            data-address-sub-county="{{ $address->sub_county }}"
                            data-address-line1="{{ $address->address_line1 }}"
                            data-address-line2="{{ $address->address_line2 }}"
                            data-address-estate="{{ $address->estate }}"
                            data-address-landmark="{{ preg_replace('/\s+/', ' ', (string) $address->landmark) }}"
                            data-address-pincode="{{ $address->pincode }}"
                            data-address-default="{{ $address->is_default ? '1' : '0' }}">
                        Edit
                    </button>

                    <button type="button"
                            class="btn btn-outline-danger checkout-address-action"
                            data-address-delete
                            data-address-id="{{ $address->id }}"
                            data-address-label="{{ $address->label }}"
                            data-delete-url="{{ route('user.checkout.addresses.destroy', ['address' => $address->id], false) }}">
                        Delete
                    </button>

                    <a href="{{ route('user.account', [], false) }}" class="checkout-address-manage">
                        Full address book
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@endif
