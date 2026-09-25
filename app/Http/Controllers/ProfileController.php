<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return $this->profileView($request, false);
    }

    public function edit(Request $request): View
    {
        return $this->profileView($request, true);
    }

    private function profileView(Request $request, bool $editing): View
    {
        $user = auth()->user();

        $activeSessions = DB::table('sessions')->where('user_id', $user->id)->count();

        $currentSessionId = $request->session()->getId();
        $currentSession = DB::table('sessions')->where('id', $currentSessionId)->first();

        return view('profile.index', [
            'user' => $user,
            'activeSessions' => $activeSessions,
            'currentIp' => $currentSession?->ip_address,
            'editing' => $editing,
        ]);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'email' => ['sometimes', 'required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,webp,gif', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');

            if ($avatarPath && $user->profile_photo_path && $user->profile_photo_path !== $avatarPath) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $validated['profile_photo_path'] = $avatarPath;
        } elseif ($request->boolean('remove_avatar') && $user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);

            $validated['profile_photo_path'] = null;
        }

        $user->update($validated);

        if ($user->wasChanged('phone')) {
            $user->forceFill(['phone_verified_at' => null])->save();
        }

        $this->recordAudit('Profile updated', 'User', $user->id);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Profile updated successfully.']);
        }

        return back()->with('status', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): JsonResponse|RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'Your current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        $this->recordAudit('Password changed', 'User', $user->id);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Password changed successfully.']);
        }

        return back()->with('status', 'Password changed successfully.');
    }
}
