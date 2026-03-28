<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\UpdatePasswordRequest;
use App\Http\Requests\Front\UpdateProfileRequest;
use App\Http\Requests\Front\UpsertUserAddressRequest;
use App\Mail\PendingEmailVerification;
use App\Models\Country;
use App\Models\County;
use App\Models\SubCounty;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Front\WalletService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class AccountController extends Controller
{
    public function __construct(private readonly WalletService $walletService)
    {
    }

    public function edit(): View
    {
        $countries = Country::query()
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN LOWER(name) = 'kenya' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($countries->isEmpty()) {
            $countries = collect(config('locations.country_suggestions', []))
                ->map(fn (array $country) => (object) [
                    'id' => null,
                    'name' => $country['name'],
                ])
                ->values();
        }

        return view('front.account.edit', [
            'user' => auth()->user(),
            'countries' => $countries,
            'savedAddresses' => auth()->user()->addresses()
                ->orderByDesc('is_default')
                ->latest('updated_at')
                ->get(),
            'walletBalance' => $this->walletService->currentBalanceForUser((int) auth()->id()),
            'walletEntries' => $this->walletService->recentEntriesForUser((int) auth()->id()),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $requestedEmail = $validated['email'];

        unset($validated['email']);

        $user->fill($validated);

        $emailMessage = null;

        if (strcasecmp($requestedEmail, $user->email) === 0) {
            if ($user->hasPendingEmailChange()) {
                $user->pending_email = null;
                $user->email_change_requested_at = null;
                $emailMessage = 'The pending email change was canceled. Your current email remains active.';
            }
        } elseif ($user->hasPendingEmailChange() && strcasecmp($requestedEmail, $user->pending_email) === 0) {
            // Keep the existing pending email request as-is.
        } else {
            $user->pending_email = Str::lower($requestedEmail);
            $user->email_change_requested_at = now();
            $emailMessage = 'We sent a verification link to ' . $user->pending_email . '. Verify it before the login email changes.';
        }

        $user->save();
        $this->syncDefaultSavedAddressFromProfile($user);

        if ($emailMessage && $user->hasPendingEmailChange()) {
            $this->sendPendingEmailVerification($user);
        }

        $response = redirect()
            ->route('user.account')
            ->with('success', 'Your profile details have been updated.');

        if ($emailMessage) {
            $response->with('info', $emailMessage);
        }

        return $response;
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => $request->validated()['new_password'],
            'remember_token' => Str::random(60),
        ])->save();

        $request->session()->regenerate();

        return redirect()
            ->route('user.account')
            ->with('password_success', 'Your password has been updated.');
    }

    public function storeAddress(UpsertUserAddressRequest $request): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user) {
            $address = $user->addresses()->create($request->addressAttributes());

            $shouldBeDefault = $request->shouldMakeDefault() || ! $user->addresses()->whereKeyNot($address->id)->exists();

            if ($shouldBeDefault) {
                $this->markAddressAsDefault($user, $address);
            }
        });

        return redirect()
            ->route('user.account')
            ->with('address_success', 'The new saved address has been added.');
    }

    public function updateAddress(UpsertUserAddressRequest $request, UserAddress $address): RedirectResponse
    {
        $user = $request->user();
        $address = $this->ownedAddress($user, $address);

        DB::transaction(function () use ($request, $user, $address) {
            $address->fill($request->addressAttributes());
            $address->save();

            if ($address->is_default || $request->shouldMakeDefault()) {
                $this->markAddressAsDefault($user, $address);
            }
        });

        return redirect()
            ->route('user.account')
            ->with('address_success', 'The saved address has been updated.');
    }

    public function destroyAddress(Request $request, UserAddress $address): RedirectResponse
    {
        $user = $request->user();
        $address = $this->ownedAddress($user, $address);

        DB::transaction(function () use ($user, $address) {
            $wasDefault = $address->is_default;
            $address->delete();

            if (! $wasDefault) {
                return;
            }

            $replacement = $user->addresses()
                ->latest('updated_at')
                ->first();

            if ($replacement) {
                $this->markAddressAsDefault($user, $replacement);
                return;
            }

            $this->syncUserProfileAddress($user, null);
        });

        return redirect()
            ->route('user.account')
            ->with('address_success', 'The saved address has been removed.');
    }

    public function setDefaultAddress(Request $request, UserAddress $address): RedirectResponse
    {
        $user = $request->user();
        $address = $this->ownedAddress($user, $address);

        DB::transaction(fn () => $this->markAddressAsDefault($user, $address));

        return redirect()
            ->route('user.account')
            ->with('address_success', 'The default delivery address has been updated.');
    }

    public function resendPendingEmailVerification(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasPendingEmailChange()) {
            return redirect()
                ->route('user.account')
                ->with('info', 'There is no pending email change to verify right now.');
        }

        $user->forceFill([
            'email_change_requested_at' => now(),
        ])->save();

        $this->sendPendingEmailVerification($user);

        return redirect()
            ->route('user.account')
            ->with('info', 'We sent a fresh verification link to ' . $user->pending_email . '.');
    }

    public function verifyPendingEmail(Request $request, User $user, string $hash): RedirectResponse
    {
        if (! $user->hasPendingEmailChange()) {
            return $this->verificationRedirect('That email change request is no longer available.');
        }

        if (! hash_equals($hash, sha1(Str::lower($user->pending_email)))) {
            abort(403);
        }

        $nextEmail = Str::lower($user->pending_email);

        $emailTaken = User::query()
            ->where('email', $nextEmail)
            ->whereKeyNot($user->id)
            ->exists();

        if ($emailTaken) {
            return $this->verificationRedirect('That email address is no longer available. Please request the change again.');
        }

        $user->forceFill([
            'email' => $nextEmail,
            'pending_email' => null,
            'email_change_requested_at' => null,
            'email_verified_at' => now(),
        ])->save();

        return $this->verificationRedirect('Your new email address has been verified and is now active.');
    }

    public function counties(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country_id' => ['required', 'integer', 'exists:countries,id'],
        ]);

        $counties = County::query()
            ->where('country_id', $validated['country_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'counties' => $counties->map(fn (County $county) => [
                'id' => $county->id,
                'name' => $county->name,
            ])->values(),
        ]);
    }

    public function subCounties(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'county_id' => ['required', 'integer', 'exists:counties,id'],
        ]);

        $subCounties = SubCounty::query()
            ->where('county_id', $validated['county_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'sub_counties' => $subCounties->map(fn (SubCounty $subCounty) => [
                'id' => $subCounty->id,
                'name' => $subCounty->name,
            ])->values(),
        ]);
    }

    protected function sendPendingEmailVerification(User $user): void
    {
        rescue(
            fn () => Mail::to($user->pending_email)->send(
                new PendingEmailVerification($user, $this->pendingEmailVerificationUrl($user))
            ),
            report: false
        );
    }

    protected function pendingEmailVerificationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'user.account.email.verify',
            now()->addMinutes(60),
            [
                'user' => $user->id,
                'hash' => sha1(Str::lower((string) $user->pending_email)),
            ]
        );
    }

    protected function verificationRedirect(string $message): RedirectResponse
    {
        $route = Auth::guard('web')->check()
            ? route('user.account', [], false)
            : route('user.login', [], false);

        return redirect()->to($route)->with('success', $message);
    }

    protected function ownedAddress(User $user, UserAddress $address): UserAddress
    {
        abort_unless($address->user_id === $user->id, 404);

        return $address;
    }

    protected function markAddressAsDefault(User $user, UserAddress $address): void
    {
        $user->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);

        if (! $address->is_default) {
            $address->forceFill(['is_default' => true])->save();
        }

        $this->syncUserProfileAddress($user, $address->fresh());
    }

    protected function syncDefaultSavedAddressFromProfile(User $user): void
    {
        $defaultAddress = $user->defaultAddress()->first();

        if (! $defaultAddress) {
            return;
        }

        $defaultAddress->forceFill($this->profileAddressAttributes($user))->save();
    }

    protected function syncUserProfileAddress(User $user, ?UserAddress $address): void
    {
        $user->forceFill([
            'address_line1' => $address?->address_line1,
            'address_line2' => $address?->address_line2,
            'country' => $address?->country ?? 'Kenya',
            'county' => $address?->county,
            'sub_county' => $address?->sub_county,
            'estate' => $address?->estate,
            'landmark' => $address?->landmark,
        ])->save();
    }

    protected function profileAddressAttributes(User $user): array
    {
        return [
            'address_line1' => $user->address_line1,
            'address_line2' => $user->address_line2,
            'country' => $user->country ?: 'Kenya',
            'county' => $user->county,
            'sub_county' => $user->sub_county,
            'estate' => $user->estate,
            'landmark' => $user->landmark,
        ];
    }
}
