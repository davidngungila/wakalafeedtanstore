@extends('layouts.app')

@section('title', 'Active Sessions')

@section('content')
    <div class="view-head">
        <div>
            <h2>Active Sessions</h2>
            <p class="sub">Monitor current sign-ins across your users, devices, and network locations.</p>
        </div>
        <div class="view-actions">
            <a class="btn btn-ghost" href="{{ route('users.index') }}">Back to users</a>
        </div>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <span class="link">{{ $sessions->total() }} active {{ $sessions->total() === 1 ? 'session' : 'sessions' }}</span>
            <span class="cell-sub">Sessions with no activity for more than {{ $sessionLifetime }} minutes are excluded.</span>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Device</th>
                        <th>IP address</th>
                        <th>Last activity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sessions as $session)
                        <tr>
                            <td>
                                <div class="cell-main">
                                    <div class="avatar">{{ strtoupper(substr($session['user_name'] ?? '?', 0, 2)) }}</div>
                                    <div>
                                        <div class="cell-title">{{ $session['user_name'] }}</div>
                                        <div class="cell-sub">{{ $session['user_email'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cell-title">{{ $session['device'] }} · {{ $session['browser'] }}</div>
                                <div class="cell-sub">{{ \Illuminate\Support\Str::limit($session['ua'] ?: 'Unknown user agent', 72) }}</div>
                            </td>
                            <td class="cell-sub">{{ $session['ip'] }}</td>
                            <td>
                                <div class="cell-title">{{ $session['last_seen']->format('d M Y H:i') }}</div>
                                <div class="cell-sub">{{ $session['last_seen']->diffForHumans() }}</div>
                            </td>
                            <td>
                                @if ($session['is_current'])
                                    <span class="tag tag-green">This device</span>
                                @else
                                    <span class="tag tag-grey">Active</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state">
                                <h4>No active sessions</h4>
                                <p>Active user sessions will appear here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $sessions->links('pagination.pager') }}
@endsection
