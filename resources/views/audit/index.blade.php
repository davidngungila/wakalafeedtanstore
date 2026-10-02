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
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-state"><h4>No audit logs</h4><p>Actions will appear here as they happen.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->links() }}
    </div>

    <div class="modal-backdrop" id="auditLogModal">
        <style>
            #auditLogModal .detail-list{display:flex;flex-direction:column;gap:8px;}
            #auditLogModal .detail-row{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;padding:9px 0;border-bottom:1px solid var(--line);}
            #auditLogModal .detail-row:last-child{border-bottom:none;}
            #auditLogModal .dk{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-soft);font-weight:700;flex:none;}
            #auditLogModal .dv{font-size:13.5px;color:var(--coffee-900);font-weight:600;text-align:right;word-break:break-word;}
            #auditLogModal table{width:100%;border-collapse:collapse;font-size:12.5px;}
            #auditLogModal th{text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--ink-soft);padding:6px 8px;border-bottom:1px solid var(--line);}
            #auditLogModal td{padding:6px 8px;border-bottom:1px solid var(--line);}
        </style>
        <div class="popup" style="max-width:520px; width:100%; margin:auto;">
            <div class="modal-head">
                <h3>Audit log entry</h3>
                <button class="modal-close" onclick="closeModal('auditLogModal')">✕</button>
            </div>
            <div class="modal-body">
                <div class="detail-list" style="margin-bottom:14px;">
                    <div class="detail-row"><span class="dk">When</span><span class="dv" id="auditModalWhen">—</span></div>
                    <div class="detail-row"><span class="dk">User</span><span class="dv" id="auditModalUser">—</span></div>
                    <div class="detail-row"><span class="dk">Action</span><span class="dv"><span class="tag tag-terracotta" id="auditModalActionTag">—</span></span></div>
                    <div class="detail-row"><span class="dk">Entity</span><span class="dv" id="auditModalEntity">—</span></div>
                    <div class="detail-row"><span class="dk">Entity ID</span><span class="dv" id="auditModalEntityId">—</span></div>
                    <div class="detail-row"><span class="dk">IP address</span><span class="dv" id="auditModalIp">—</span></div>
                </div>
                <div id="auditModalBody"></div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-primary" onclick="closeModal('auditLogModal')">Close</button>
            </div>
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

        const AUDIT_FIELD_LABELS = {
            amount: 'Amount', type: 'Type', network_id: 'Network ID', date: 'Date',
            name: 'Name', email: 'Email', phone: 'Phone', role: 'Role', status: 'Status',
            reference: 'Reference', method: 'Method', variance: 'Variance', tie_out: 'Tie-out',
            opening_cash: 'Opening cash', deposits: 'Deposits', withdrawals: 'Withdrawals',
            total_volume: 'Total volume', total_commission: 'Commission', cash: 'Cash',
            float_total: 'Float total', count: 'Count', shift: 'Shift',
        };

        const auditLabel = (key) => AUDIT_FIELD_LABELS[key] || key.replace(/_/g, ' ').replace(/^./, c => c.toUpperCase());

        function auditValue(value) {
            if (value === null || value === undefined) return '—';
            if (typeof value === 'object') return JSON.stringify(value);
            return String(value);
        }

        function buildAuditDetails(details) {
            const keys = Object.keys(details).filter(k => k !== 'old' && k !== 'new');
            const oldValues = (details.old && typeof details.old === 'object') ? details.old : null;
            const newValues = (details.new && typeof details.new === 'object') ? details.new : null;

            let html = '<div class="detail-list">';

            keys.forEach(key => {
                html += '<div class="detail-row"><span class="dk">' + auditEsc(auditLabel(key)) + '</span><span class="dv">' + auditEsc(auditValue(details[key])) + '</span></div>';
            });

            if (oldValues || newValues) {
                const diffKeys = [...new Set([...Object.keys(oldValues || {}), ...Object.keys(newValues || {})])];
                const changed = diffKeys.filter(key => auditValue(oldValues?.[key]) !== auditValue(newValues?.[key]));

                if (changed.length === 0) {
                    html += '<div class="detail-row" style="padding-top:12px;margin-top:6px;border-top:1px solid var(--line);"><span class="dk">Changes</span><span class="dv"><em style="color:var(--ink-soft);">No field values changed.</em></span></div>';
                } else {
                    html += '<div class="detail-row" style="padding-top:12px;margin-top:6px;border-top:1px solid var(--line);"><span class="dk">Changes</span><span class="dv">' + changed.length + ' field(s) changed</span></div>';
                    html += '<div class="table-scroll" style="margin:8px 0 4px;"><table><thead><tr><th>Field</th><th>Old</th><th>New</th></tr></thead><tbody>';
                    changed.forEach(key => {
                        html += '<tr>' +
                            '<td>' + auditEsc(auditLabel(key)) + '</td>' +
                            '<td style="color:var(--danger);">' + auditEsc(auditValue(oldValues?.[key])) + '</td>' +
                            '<td style="color:var(--acacia-600);font-weight:700;">' + auditEsc(auditValue(newValues?.[key])) + '</td>' +
                            '</tr>';
                    });
                    html += '</tbody></table></div>';
                }
            }

            if (keys.length === 0 && !oldValues && !newValues) {
                html += '<div class="detail-row"><span class="dk">Details</span><span class="dv">—</span></div>';
            }

            return html + '</div>';
        }

        function auditEsc(value) {
            return String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        function openAuditModal(tr) {
            let details = tr.dataset.details || '{}';
            try { details = JSON.parse(details); } catch (err) { details = {}; }

            document.getElementById('auditModalWhen').textContent = tr.dataset.when;
            document.getElementById('auditModalUser').textContent = tr.dataset.user + (tr.dataset.useremail ? ' (' + tr.dataset.useremail + ')' : '');
            document.getElementById('auditModalActionTag').textContent = tr.dataset.action;
            document.getElementById('auditModalEntity').textContent = tr.dataset.entity || '—';
            document.getElementById('auditModalEntityId').textContent = tr.dataset.entityid || '—';
            document.getElementById('auditModalIp').textContent = tr.dataset.ip || '—';
            document.getElementById('auditModalBody').innerHTML = buildAuditDetails(details);

            openModal('auditLogModal');
        }

        document.querySelectorAll('#logsBody tr[data-action]').forEach(tr => {
            tr.style.cursor = 'pointer';
            tr.addEventListener('click', () => openAuditModal(tr));
        });
    </script>
@endsection