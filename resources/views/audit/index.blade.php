@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
    <div class="view-head">
        <div>
            <h2>Audit Logs</h2>
            <p class="sub">Every significant action recorded for compliance and accountability.</p>
        </div>
        @include('exports._export-modal', ['route' => $exportRoute, 'columns' => $exportColumns, 'title' => 'Audit Log'])
    </div>

    <form method="GET" action="{{ route('audit.index') }}">
        <div class="table-card">
            <div class="table-toolbar">
                <div class="chip-filters">
                    <span class="link" style="font-size:13px;color:var(--ink-soft);font-weight:600;">Action:</span>
                    <select name="action" onchange="this.form.submit()" style="padding:9px 12px;border:1.5px solid var(--line);border-radius:10px;font-size:13px;background:var(--white);color:var(--coffee-700);font-weight:600;">
                        <option value="all" {{ $activeAction === 'all' ? 'selected' : '' }}>All actions</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action['value'] }}" {{ $activeAction === $action['value'] ? 'selected' : '' }}>{{ $action['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="table-search" style="margin-left:auto;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" placeholder="Search logs…" oninput="filterLogs(this.value)">
                </div>
            </div>
        </div>
    </form>

    <div class="table-card" style="margin-top:-24px;">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Details</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody id="logsBody">
                    @forelse ($logs as $log)
                        <tr data-search="{{ strtolower($log->action.' '.($log->user?->name ?? '').' '.($log->entity_type ?? '')) }}"
                            data-when="{{ $log->created_at->format('d M Y H:i') }}"
                            data-user="{{ $log->user?->name ?? 'System' }}"
                            data-useremail="{{ $log->user?->email ?? '' }}"
                            data-action="{{ $log->action }}"
                            data-entity="{{ $log->entity_type ?? '' }}"
                            data-entityid="{{ $log->entity_id ?? '' }}"
                            data-ip="{{ $log->ip_address ?? '' }}"
                            data-details="{{ json_encode($log->details ?? []) }}">
                            <td>
                                <div class="cell-title">{{ $log->created_at->format('d M Y H:i') }}</div>
                                <div class="cell-sub">{{ $log->created_at->diffForHumans() }}</div>
                            </td>
                            <td>
                                <div class="cell-main">
                                    <div class="avatar">{{ strtoupper(substr($log->user?->name ?? '?', 0, 2)) }}</div>
                                    <div>
                                        <div class="cell-title">{{ $log->user?->name ?? 'System' }}</div>
                                        <div class="cell-sub">{{ $log->user?->email ?? '' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="tag tag-terracotta" style="white-space:normal;">{{ $log->action }}</span>
                            </td>
                            <td>
                                <div class="cell-sub">{{ $log->entity_type ?? '—' }}</div>
                                <div class="cell-title" style="font-size:12px;">#{{ $log->entity_id ?? '—' }}</div>
                            </td>
                            <td>
                                @forelse ($log->details ?? [] as $key => $value)
                                    <span class="tag tag-grey" style="margin:1px 2px 1px 0;">{{ $key }}: {{ is_array($value) || is_object($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) : $value }}</span>
                                @empty
                                    <span class="cell-sub">—</span>
                                @endforelse
                            </td>
                            <td class="cell-sub">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state"><h4>No audit logs</h4><p>Actions will appear here as they happen.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function filterLogs(q) {
            q = q.toLowerCase();
            document.querySelectorAll('#logsBody tr[data-search]').forEach(tr => {
                tr.style.display = (!q || tr.dataset.search.includes(q)) ? 'table-row' : 'none';
            });
        }

        bindRowClick('#logsBody tr[data-action]', tr => {
            let details = tr.dataset.details || '{}';
            try { details = JSON.parse(details); } catch (err) { details = {}; }
            const detailEntries = Object.keys(details).map(k => [
                k,
                details[k] !== null && typeof details[k] === 'object'
                    ? JSON.stringify(details[k])
                    : String(details[k]),
            ]);
            return [
                ['When', tr.dataset.when],
                ['User', tr.dataset.user + (tr.dataset.useremail ? ' (' + tr.dataset.useremail + ')' : '')],
                ['Action', { __html: '<span class="tag tag-terracotta">' + tr.dataset.action + '</span>' }],
                ['Entity', tr.dataset.entity || '—'],
                ['Entity ID', tr.dataset.entityid || '—'],
                ['IP address', tr.dataset.ip || '—'],
                ...detailEntries,
            ];
        }, 'Audit log entry');
    </script>
@endsection