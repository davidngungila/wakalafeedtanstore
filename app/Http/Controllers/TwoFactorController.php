<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $userId = $request->session()->get('two_factor_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $email = $request->session()->get('two_factor_user_email');

        return view('auth.two-factor', ['email' => $email ?: $request->input('email', '')]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $userId = $request->session()->get('two_factor_user_id');

        if (! $userId) {
            return redirect()->route('login')->withErrors(['email' => 'Your sign-in session has expired. Please sign in again.']);
        }

        $user = User::find($userId);

        if (! $user || ! $user->two_factor_enabled || ! $user->two_factor_secret) {
            return redirect()->route('login')->withErrors(['email' => 'Your sign-in session has expired. Please sign in again.']);
        }

        $code = $request->string('code')->trim()->toString();

        $recovered = false;

        if (! TwoFactor::verifyCode(Crypt::decryptString($user->two_factor_secret), $code)) {
            $codes = $user->two_factor_recovery_codes ?? [];
            $hash = TwoFactor::hashRecoveryCode($code);

            if (! in_array($hash, $codes, true)) {
                return back()->withErrors(['code' => 'The code is invalid or has expired.']);
            }

            $recovered = true;

            $user->forceFill([
                'two_factor_recovery_codes' => array_values(array_filter($codes, fn (string $candidate) => $candidate !== $hash)),
            ])->save();
        }

        $request->session()->forget(['two_factor_user_id', 'two_factor_user_email']);

        Auth::login($user);

        return $this->completeLogin($request, $recovered);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(['two_factor_user_id', 'two_factor_user_email']);

        return redirect()->route('login');
    }
}
