@extends('layouts.app')

@section('title', 'Reconciliation')

@section('content')
    <div class="view-head">
        <div>
            <h2>Reconciliation</h2>
            <p class="sub">Compare counted till cash and network floats against system balances every day.</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('reconciliation.create') }}" class="btn btn-primary">+ New reconciliation</a>
        </div>
        @include('exports._export-modal', ['route' => $exportRoute, 'columns' => $exportColumns, 'title' => 'Reconciliation'])
    </div>

    <div class="stat-grid">
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1"></rect><path d="m9 14 2 2 4-4"></path></svg></div><span class="stat-trend up">{{ $totals['reconciled'] }}</span></div>
            <div class="stat-value">{{ $totals['reconciled'] }}</div>
            <div class="stat-label">Reconciled sessions</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg></div></div>
            <div class="stat-value">{{ $totals['open'] }}</div>
            <div class="stat-label">Open / pending</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--danger-100);--stat-fg:var(--danger);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12a8 8 0 1 1-11-7.4"></path><path d="M20 3v6h-6"></path></svg></div></div>
            <div class="stat-value">{{ $totals['variance'] }}</div>
            <div class="stat-label">Variance sessions</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--terracotta-100);--stat-fg:var(--terracotta-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16"></path><path d="M4 14h12"></path><path d="M4 8h8"></path></svg></div></div>
            <div class="stat-value">@money($totals['varianceAmount'])</div>
            <div class="stat-label">Open variance amount</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12a8 8 0 1 1-11-7.4"></path><path d="M20 3v6h-6"></path></svg></div></div>
            <div class="stat-value">{{ $totals['resolved'] }}</div>
            <div class="stat-label">Resolved via corrections</div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Expected cash</th>
                        <th>Counted cash</th>
                        <th>Cash variance</th>
                        <th>Float variance</th>
                        <th>Status</th>
                        <th>By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="reconRows">
                    @forelse ($records as $record)
                        <tr data-id="{{ $record->id }}"
                            data-key="{{ $record->getRouteKey() }}"
                            data-date="{{ $record->reconciliation_date }}"
                            data-code="{{ $record->code }}"
                            data-expected="{{ $record->expected_cash }}"
                            data-counted="{{ $record->counted_cash }}"
                            data-cashvar="{{ $record->cash_variance }}"
                            data-floatvar="{{ $record->float_variance }}"
                            data-status="{{ $record->status }}"
                            data-reconciler="{{ $record->reconciler?->name }}"
                            data-notes="{{ $record->notes }}">
                            <td>
                                <div class="cell-title">{{ $record->reconciliation_date }}</div>
                                <div class="cell-sub">#{{ $record->code }}</div>
                            </td>
                            <td>@money($record->expected_cash)</td>
                            <td>@money($record->counted_cash)</td>
                            <td>
                                @if ($record->cash_variance == 0)
                                    <span class="tag tag-green">Balanced</span>
                                @else
                                    <span class="tag {{ $record->cash_variance > 0 ? 'tag-gold' : 'tag-red' }}">{{ $record->cash_variance > 0 ? '+' : '' }}@money($record->cash_variance)</span>
                                @endif
                            </td>
                            <td>
                                @if ($record->float_variance == 0)
                                    <span class="tag tag-green">Balanced</span>
                                @else
                                    <span class="tag tag-terracotta">{{ $record->float_variance > 0 ? '+' : '' }}@money($record->float_variance)</span>
                                @endif
                            </td>
                            <td><span class="tag {{ status_badge($record->status) }}">{{ ucfirst($record->status) }}</span></td>
                            <td class="cell-sub">{{ $record->reconciler?->name ?? '—' }}</td>
                            <td>
                                <div class="row-actions">
                                    <button onclick="window.location='{{ route('reconciliation.show', $record) }}'" title="More details" style="width:auto;padding:0 12px;gap:6px;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"></path></svg>
                                        <span>More</span>
                                    </button>
                                    @if(is_admin())
                                        <button type="button" onclick="openDeleteReconModal('{{ $record->getRouteKey() }}', '{{ $record->code }}', '{{ $record->reconciliation_date }}')" title="Delete reconciled" style="width:32px;height:32px;border-radius:7px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--danger);">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-state"><h4>No reconciliations yet</h4><p>Run your first end-of-day reconciliation.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-backdrop" id="deleteReconModal">
        <div class="modal" style="max-width:440px;">
            <div class="modal-head">
                <h3>Delete Reconciliation</h3>
                <button class="modal-close" onclick="closeModal('deleteReconModal')">✕</button>
            </div>
            <div class="modal-body">
                <p style="font-size:13.5px;color:var(--ink-soft);">Delete reconciliation <strong id="deleteReconCode"></strong> for <strong id="deleteReconDate"></strong>? This cannot be undone. Its corrections will also be removed.</p>
            </div>
            <div class="modal-foot">
                <button class="btn btn-ghost" onclick="closeModal('deleteReconModal')">Cancel</button>
                <button class="btn btn-danger" onclick="confirmDeleteRecon()">Delete</button>
            </div>
        </div>
    </div>
    <form id="deleteReconForm" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
    </form>

@endsection

@section('scripts')
    <script>
        function reconFmt(n) { return 'TZS ' + Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); }

        bindRowClick('#reconRows tr[data-code]', tr => {
            const cashVar = parseFloat(tr.dataset.cashvar) || 0;
            const floatVar = parseFloat(tr.dataset.floatvar) || 0;
            const cashTag = cashVar === 0
                ? '<span class="tag tag-green">Balanced</span>'
                : (cashVar > 0
                    ? '<span class="tag tag-gold">+' + reconFmt(cashVar) + '</span>'
                    : '<span class="tag tag-red">' + reconFmt(cashVar) + '</span>');
            const floatTag = floatVar === 0
                ? '<span class="tag tag-green">Balanced</span>'
                : '<span class="tag tag-terracotta">' + (floatVar > 0 ? '+' : '') + reconFmt(floatVar) + '</span>';
            return [
                ['Date', tr.dataset.date],
                ['Code', '#' + tr.dataset.code],
                ['Expected cash', reconFmt(parseFloat(tr.dataset.expected) || 0)],
                ['Counted cash', reconFmt(parseFloat(tr.dataset.counted) || 0)],
                ['Cash variance', { __html: cashTag }],
                ['Float variance', { __html: floatTag }],
                ['Status', { __html: statusBadgeHtml(tr.dataset.status) }],
                ['Reconciled by', tr.dataset.reconciler || '—'],
                ['Notes', tr.dataset.notes || '—'],
            ];
        }, tr => 'Reconciliation #' + tr.dataset.code, tr => [{
            label: 'More details',
            class: 'btn-primary',
            action: () => window.location = '{{ route('reconciliation.show', ':key') }}'.replace(':key', tr.dataset.key),
        }]);

        document.querySelectorAll('[data-recon-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: () => setTimeout(() => location.reload(), 600) });
            });
        });

        let deleteReconKey = null;
        function openDeleteReconModal(key, code, date) {
            deleteReconKey = key;
            document.getElementById('deleteReconCode').textContent = '#' + code;
            document.getElementById('deleteReconDate').textContent = date;
            const form = document.getElementById('deleteReconForm');
            form.action = '/reconciliation/' + encodeURIComponent(key);
            openModal('deleteReconModal');
        }
        function confirmDeleteRecon() {
            if (!deleteReconKey) return;
            const form = document.getElementById('deleteReconForm');
            submitForm(form, { method: 'POST', done: () => setTimeout(() => location.reload(), 500) });
        }
        window.openDeleteReconModal = openDeleteReconModal;
        window.confirmDeleteRecon = confirmDeleteRecon;
    </script>
@endsection