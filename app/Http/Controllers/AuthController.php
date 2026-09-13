<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $user->is_active) {
            return $this->loginFailed($request, 'This account is inactive or does not exist.');
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            return $this->loginFailed($request, 'These credentials do not match our records.');
        }

        if (! $user->two_factor_enabled) {
            Auth::login($user, $request->boolean('remember'));

            return $this->completeLogin($request);
        }

        if (! $user->two_factor_secret) {
            return $this->loginFailed($request, 'Two-factor authentication is misconfigured. Contact support.');
        }

        $request->session()->put('two_factor_user_id', $user->id);
        $request->session()->put('two_factor_user_email', $user->email);
        $request->session()->regenerate();

        $this->recordAudit('Two-factor challenge started', 'User', $user->id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'two_factor' => true,
                'message' => 'Enter your two-factor authentication code.',
                'redirect' => route('two-factor.show'),
            ]);
        }

        return redirect()->route('two-factor.show');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->recordAudit('User logged out', 'User', Auth::id());

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function loginFailed(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return back()->withErrors(['email' => $message])->withInput($request->only('email'));
    }
}
