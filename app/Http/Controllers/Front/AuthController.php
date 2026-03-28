<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\ForgotPasswordRequest;
use App\Http\Requests\Front\LoginRequest;
use App\Http\Requests\Front\RegisterRequest;
use App\Http\Requests\Front\ResetPasswordRequest;
use App\Mail\UserRegistered;
use App\Models\User;
use App\Services\Front\AuthService;
use App\Services\Front\CartService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected AuthService $authService;
    protected CartService $cartService;

    public function __construct(AuthService $authService, CartService $cartService)
    {
        $this->authService = $authService;
        $this->cartService = $cartService;
    }

    public function showLogin()
    {
        return view('front.auth.login');
    }

    public function login(LoginRequest $request)
    {
        $guestSessionId = $this->captureGuestSessionId($request);
        $data = $request->validated();
        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
            'user_type' => $data['user_type'] ?? null,
        ];

        // Pre-check inactive users so the response can be more specific than "invalid credentials".
        $user = User::query()
            ->where('email', $data['email'])
            ->when(
                !empty($data['user_type']),
                fn ($query) => $query->where('user_type', $data['user_type'])
            )
            ->first();

        if ($user && (int) $user->status === 0) {
            return $this->failedLoginResponse(
                $request,
                ['email' => ['Your account is inactive. Please contact support.']]
            );
        }

        if ($this->authService->attemptLogin($credentials, $request->boolean('remember'))) {
            $this->cartService->migrateGuestCartToUser($guestSessionId, (int) Auth::id());
            return $this->successResponse($request, 'Login successful.');
        }

        return $this->failedLoginResponse($request, ['email' => ['Invalid credentials.']]);
    }

    public function showRegister()
    {
        return view('front.auth.register');
    }

    public function register(RegisterRequest $request)
    {
        $guestSessionId = $this->captureGuestSessionId($request);
        $data = $request->validated();
        $user = $this->authService->register($data);

        Auth::login($user);
        $request->session()->regenerate();
        $this->cartService->migrateGuestCartToUser($guestSessionId, (int) $user->id);

        // Registration should not fail just because the mail transport is unavailable.
        rescue(
            fn () => Mail::to($user->email)->send(new UserRegistered($user)),
            report: false
        );

        return $this->successResponse(
            $request,
            'Registration successful.',
            'home'
        );
    }

    public function showForgotForm()
    {
        return view('front.auth.forgot_password');
    }

    public function sendResetLink(ForgotPasswordRequest $request)
    {
        $validated = $request->validated();
        $status = Password::sendResetLink(['email' => $validated['email']]);

        if ($status === Password::RESET_LINK_SENT) {
            $message = __($status);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return back()->with('success', $message);
        }

        return $this->passwordErrorResponse(
            $request,
            ['email' => [__($status)]],
            ['email']
        );
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('front.auth.reset_password', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $validated = $request->validated();
        $resetUser = null;

        $status = Password::reset(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'password_confirmation' => $validated['password_confirmation'],
                'token' => $validated['token'],
            ],
            function ($user, $password) use (&$resetUser) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
                $resetUser = $user->fresh();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            if ($resetUser && (int) $resetUser->status === 1) {
                Auth::login($resetUser);
            }

            $message = __($status);
            $redirect = Auth::check()
                ? route('home', [], false)
                : route('user.login', [], false);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect' => $redirect,
                ]);
            }

            return redirect()->to($redirect)->with('success', $message);
        }

        return $this->passwordErrorResponse(
            $request,
            ['email' => [__($status)]],
            ['email']
        );
    }

    public function logout(Request $request)
    {
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(route('home', [], false))->with('success', 'Logged out successfully.');
    }

    protected function captureGuestSessionId(Request $request): string
    {
        $guestSessionId = (string) $request->session()->get('session_id', '');

        if ($guestSessionId === '') {
            $guestSessionId = $request->session()->getId();
            $request->session()->put('session_id', $guestSessionId);
        }

        return $guestSessionId;
    }

    protected function successResponse(Request $request, string $message, string $redirectRoute = 'home')
    {
        $redirect = route($redirectRoute, [], false);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => $redirect,
            ]);
        }

        return redirect()->to($redirect)->with('success', $message);
    }

    protected function failedLoginResponse(Request $request, array $errors)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'errors' => $errors,
            ], 422);
        }

        return back()
            ->withErrors($errors)
            ->onlyInput('email', 'user_type');
    }

    protected function passwordErrorResponse(Request $request, array $errors, array $input = [])
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'errors' => $errors,
            ], 422);
        }

        $response = back()->withErrors($errors);

        if (!empty($input)) {
            return $response->withInput($request->only($input));
        }

        return $response;
    }
}
