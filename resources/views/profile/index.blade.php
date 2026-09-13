@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
    <div class="view-head">
        <div>
            <h2>My Profile</h2>
            <p class="sub">Update your personal information and change your password.</p>
        </div>
    </div>

    <div class="panel-grid">
        <div class="panel">
            <div class="panel-head">
                <h3>Profile information</h3>
                <span class="link">{{ ucfirst($user->role) }}</span>
            </div>
            <div class="panel-body">
                <form method="POST" action="{{ route('profile.update') }}" data-profile-form>
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;">
                        <div id="avatarPreview" style="width:72px;height:72px;border-radius:50%;overflow:hidden;flex:none;background:var(--sand-100);display:flex;align-items:center;justify-content:center;color:var(--ink-soft);font-weight:700;font-size:20px;border:2px solid var(--line);">
                            @if ($user->profile_photo_path)
                                <img src="{{ $user->avatarUrl() }}" style="width:100%;height:100%;object-fit:cover;">
                            @else
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            @endif
                        </div>
                        <div style="flex:1;min-width:0;">
                            <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/png,image/webp,image/gif" style="font-size:13px;max-width:100%;">
                            <p style="margin:7px 0 0;font-size:12px;color:var(--ink-soft);">JPG, PNG, WebP or GIF up to 2&nbsp;MB. Leave empty to keep the current photo.</p>
                            @if ($user->profile_photo_path)
                                <label style="display:inline-flex;align-items:center;gap:6px;font-size:12.5px;color:var(--danger);margin-top:8px;cursor:pointer;">
                                    <input type="checkbox" name="remove_avatar" value="1" style="cursor:pointer;"> Remove current photo
                                </label>
                            @endif
                        </div>
                    </div>
                    <div class="field"><label>Full name</label><input type="text" name="name" value="{{ $user->name }}" required></div>
                    <div class="field"><label>Email</label><input type="email" name="email" value="{{ $user->email }}" required></div>
                    <div class="field"><label>Phone</label><input type="text" name="phone" value="{{ $user->phone ?? '' }}" placeholder="07xxxxxxxx"></div>
                    <button type="submit" class="btn btn-primary">Save profile</button>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h3>Change password</h3>
            </div>
            <div class="panel-body">
                <form method="POST" action="{{ route('profile.password') }}" data-password-form>
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <div class="field"><label>Current password</label><input type="password" name="current_password" required autocomplete="current-password"></div>
                    <div class="field"><label>New password</label><input type="password" name="password" required minlength="6" autocomplete="new-password"></div>
                    <div class="field"><label>Confirm new password</label><input type="password" name="password_confirmation" required autocomplete="new-password"></div>
                    <button type="submit" class="btn btn-danger" style="background:var(--coffee-900);color:#fff;">Update password</button>
                </form>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Account details</h3>
        </div>
        <div class="panel-body">
            <div class="detail-grid">
                <div class="detail-item"><div class="dk">Role</div><div class="dv">{{ ucfirst($user->role) }}</div></div>
                <div class="detail-item"><div class="dk">Member since</div><div class="dv">{{ $user->created_at->format('d M Y') }}</div></div>
                <div class="detail-item"><div class="dk">Last login</div><div class="dv">{{ $user->last_login_at?->format('d M Y H:i') ?? 'Never' }}</div></div>
                <div class="detail-item"><div class="dk">Linked cash point</div><div class="dv">{{ $user->agent?->name ?? '—' }}</div></div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const avatarInput = document.getElementById('avatarInput');
        if (avatarInput) {
            avatarInput.addEventListener('change', function () {
                const [file] = this.files;
                if (!file) return;
                const preview = document.getElementById('avatarPreview');
                const img = new Image();
                img.onload = () => {
                    preview.innerHTML = '';
                    preview.appendChild(img);
                };
                img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%;';
                img.src = URL.createObjectURL(file);
            });
        }

        document.querySelectorAll('[data-profile-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'PUT', done: () => toast('Profile updated successfully.', 'success') });
            });
        });

        document.querySelectorAll('[data-password-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'PUT', done: () => toast('Password changed successfully.', 'success') });
            });
        });
    </script>
@endsection