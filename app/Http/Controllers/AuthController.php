<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return $this->loginFailed($request, 'These credentials do not match our records.');
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $this->recordAudit('User logged in', 'User', $user->id, ['email' => $user->email]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Welcome back, '.$user->name.'!']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
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
