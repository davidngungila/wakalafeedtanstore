@extends('layouts.app')

@section('title', 'Journal Entries')

@section('content')
    <div class="view-head">
        <div>
            <h2>Journal Entries</h2>
            <p class="sub">The double-entry core. Each entry posts balanced debits and credits to accounts.</p>
        </div>
        <div class="view-actions">
            @if (is_admin())
                <button class="btn btn-primary" onclick="openEntryDrawer()">New journal entry</button>
            @endif
        </div>
    </div>

    @include('finance._nav')

    <div class="stat-grid">
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg></div></div>
            <div class="stat-value">{{ $totals['total'] }}</div>
            <div class="stat-label">Total entries</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3"></path><circle cx="12" cy="12" r="10"></circle></svg></div></div>
            <div class="stat-value">{{ $totals['drafts'] }}</div>
            <div class="stat-label">Drafts</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 14 2 2 4-4"></path><circle cx="12" cy="12" r="10"></circle></svg></div></div>
            <div class="stat-value">{{ $totals['posted'] }}</div>
            <div class="stat-label">Posted</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--sand-200);--stat-fg:var(--coffee-700);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3v2m0 0V5m0 0H4m0 0V3m0 2v2m4-1V5m8 0h2m0 0V3m0 2h2m0 0V5m0 2v2m0 0h2".replace('v2','')></path><path d="M4 10h16v10H4z"></path></svg></div></div>
            <div class="stat-value">{{ $accounts->count() }}</div>
            <div class="stat-label">Accounts to post to</div>
        </div>
    </div>

    <div class="table-toolbar" style="background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);margin-bottom:24px;">
        <div class="chip-filters">
            <a href="{{ route('finance.journals.index') }}" class="chip {{ ($status ?? 'all') === 'all' ? 'active' : '' }}">All</a>
            <a href="{{ route('finance.journals.index', ['status' => 'draft']) }}" class="chip {{ ($status ?? '') === 'draft' ? 'active' : '' }}">Drafts</a>
            <a href="{{ route('finance.journals.index', ['status' => 'posted']) }}" class="chip {{ ($status ?? '') === 'posted' ? 'active' : '' }}">Posted</a>
        </div>
    </div>

    <div class="table-card" style="margin-top:-8px;">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th>Debits</th>
                        <th>Credits</th>
                        <th>Status</th>
                        <th>By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr class="row-click" style="cursor:pointer;" onclick="window.location='{{ route('finance.journals.show', $entry) }}'">
                            <td class="cell-title">{{ $entry->entry_date->format('d M Y') }}</td>
                            <td class="cell-title"><a href="{{ route('finance.journals.show', $entry) }}" onclick="event.stopPropagation()" style="color:var(--terracotta-600);text-decoration:none;font-weight:700;">{{ $entry->reference }}</a></td>
                            <td class="cell-sub">{{ Str::limit($entry->description, 55) }}</td>
                            <td>@money($entry->totalDebits())</td>
                            <td>@money($entry->totalCredits())</td>
                            <td><span class="tag {{ journal_status_badge($entry->status) }}">{{ journal_status_label($entry->status) }}</span></td>
                            <td class="cell-sub">{{ $entry->creator?->name ?? '—' }}</td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ route('finance.journals.show', $entry) }}" onclick="event.stopPropagation()" title="View details" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </a>
                                    @if (is_admin() && $entry->status === 'draft')
                                        <form method="POST" action="{{ route('finance.journals.post', $entry) }}" onsubmit="return confirm('Post {{ $entry->reference }}?')" onclick="event.stopPropagation()">
                                            @csrf
                                            <button class="warn" title="Post">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 14 2 2 4-4"></path><circle cx="12" cy="12" r="10"></circle></svg>
                                            </button>
                                        </form>
                                    @endif
                                    @if (is_admin() && $entry->status === 'posted')
                                        <form method="POST" action="{{ route('finance.journals.reverse', $entry) }}" onsubmit="return confirm('Reverse {{ $entry->reference }} with an offsetting entry?')" onclick="event.stopPropagation()">
                                            @csrf
                                            <button class="danger" title="Reverse">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-state">No journal entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($entries->hasPages())
            <div class="table-pager">
                <span class="pager-info">Page {{ $entries->currentPage() }} of {{ $entries->lastPage() }}</span>
                <div class="pager-pages">{{ $entries->links() }}</div>
            </div>
        @endif
    </div>

    @if (is_admin())
        <div class="modal-backdrop" id="entryDrawerBackdrop" onclick="if(event.target===this)closeEntryDrawer()">
            <div class="modal">
                <div class="modal-head">
                    <h3>New journal entry</h3>
                    <button type="button" class="modal-close" onclick="closeEntryDrawer()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('finance.journals.store') }}" class="modal-body" id="journalEntryForm">
                    @csrf
                    <div class="field">
                        <label>Entry date</label>
                        <input type="date" name="entry_date" value="{{ today()->toDateString() }}" required>
                    </div>
                    <div class="field">
                        <label>Description</label>
                        <input type="text" name="description" placeholder="What does this entry record?" maxlength="255" required>
                    </div>
                    <div class="field">
                        <label>Reference</label>
                        <input type="text" name="reference" placeholder="Optional — auto-generated if blank" maxlength="40">
                    </div>

                    <div class="field">
                        <label>Lines</label>
                        <div id="journalLines"></div>
                        <button type="button" class="btn btn-soft btn-sm" style="margin-top:10px;" onclick="addLine()">+ Add line</button>
                    </div>

                    <div class="receipt" style="margin-bottom:14px;">
                        <div class="receipt-row"><span>Total debits</span><b id="totalDebit">TZS 0</b></div>
                        <div class="receipt-row"><span>Total credits</span><b id="totalCredit">TZS 0</b></div>
                        <div class="receipt-row"><span>Difference</span><b id="balanceDiff">TZS 0</b></div>
                    </div>

                    <div class="field">
                        <label>Status</label>
                        <select name="status">
                            <option value="draft">Save as draft</option>
                            <option value="posted">Post immediately</option>
                        </select>
                    </div>

                    <div class="modal-foot" style="padding:4px 0 0;">
                        <button type="button" class="btn btn-ghost" onclick="closeEntryDrawer()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save entry</button>
                    </div>
                </form>
            </div>
        </div>

        @php
            $accountOptions = $accounts->map(fn ($a) => [
                'id' => $a->id,
                'label' => $a->code.' — '.$a->name,
            ])->all();
        @endphp
        <script>
            const accountOptions = @json($accountOptions);

            function openEntryDrawer() {
                document.getElementById('journalLines').innerHTML = '';
                if (!accountOptions.length) {
                    document.getElementById('journalLines').innerHTML = '<p class="cell-sub">Create accounts in the chart of accounts first.</p>';
                } else {
                    addLine();
                    addLine();
                }
                document.getElementById('entryDrawerBackdrop').classList.add('show');
            }

            function closeEntryDrawer() {
                document.getElementById('entryDrawerBackdrop').classList.remove('show');
            }

            function addLine() {
                const host = document.getElementById('journalLines');
                if (!accountOptions.length) return;
                const wrap = document.createElement('div');
                const index = host.children.length;
                wrap.innerHTML = '' +
                    '<select data-role="account" style="padding:9px 10px;border:1.5px solid var(--line);border-radius:8px;font-size:13px;min-width:0;">' +
                        '<option value="">Account…</option>' +
                        accountOptions.map(o => '<option value="' + o.id + '">' + o.label + '</option>').join('') +
                    '</select>' +
                    '<input type="text" data-role="note" placeholder="Note" maxlength="255" style="padding:9px 10px;border:1.5px solid var(--line);border-radius:8px;font-size:13px;min-width:0;">' +
                    '<input type="number" data-role="debit" placeholder="Debit" min="0" step="0.01" value="0" oninput="recalcTotals()" style="padding:9px 10px;border:1.5px solid var(--line);border-radius:8px;font-size:13px;min-width:0;">' +
                    '<input type="number" data-role="credit" placeholder="Credit" min="0" step="0.01" value="0" oninput="recalcTotals()" style="padding:9px 10px;border:1.5px solid var(--line);border-radius:8px;font-size:13px;min-width:0;">' +
                    '<button type="button" onclick="removeLine(this)" style="border:none;background:none;color:var(--danger);font-size:16px;cursor:pointer;">&times;</button>';
                wrap.style.cssText = 'display:grid;grid-template-columns:1fr 1fr .8fr .8fr auto;gap:8px;margin-bottom:8px;align-items:center;';
                host.appendChild(wrap);
                nameLines();
                recalcTotals();
            }

            function removeLine(btn) {
                btn.parentElement.remove();
                nameLines();
                recalcTotals();
            }

            function nameLines() {
                document.querySelectorAll('#journalLines > div').forEach((row, index) => {
                    const account = row.querySelector('[data-role="account"]');
                    const note = row.querySelector('[data-role="note"]');
                    const debit = row.querySelector('[data-role="debit"]');
                    const credit = row.querySelector('[data-role="credit"]');
                    account.name = 'lines[' + index + '][account_id]';
                    note.name = 'lines[' + index + '][description]';
                    debit.name = 'lines[' + index + '][debit]';
                    credit.name = 'lines[' + index + '][credit]';
                });
            }

            function recalcTotals() {
                let debit = 0, credit = 0;
                document.querySelectorAll('#journalLines [data-role="debit"]').forEach(el => debit += (parseFloat(el.value) || 0));
                document.querySelectorAll('#journalLines [data-role="credit"]').forEach(el => credit += (parseFloat(el.value) || 0));
                const fmt = v => 'TZS ' + v.toLocaleString('en', { maximumFractionDigits: 2 });
                document.getElementById('totalDebit').textContent = fmt(debit);
                document.getElementById('totalCredit').textContent = fmt(credit);
                const diff = Math.abs(debit - credit);
                const diffEl = document.getElementById('balanceDiff');
                diffEl.textContent = fmt(diff);
                diffEl.style.color = diff < 0.01 ? 'var(--success)' : 'var(--danger)';
            }

            document.getElementById('journalEntryForm').addEventListener('submit', function (e) {
                nameLines();
                const debitEls = document.querySelectorAll('#journalLines [data-role="debit"]');
                const creditEls = document.querySelectorAll('#journalLines [data-role="credit"]');
                let rows = 0;
                debitEls.forEach((el, i) => {
                    const d = parseFloat(el.value) || 0, c = parseFloat(creditEls[i].value) || 0;
                    if (d > 0 || c > 0) rows++;
                });
                if (rows < 2) {
                    e.preventDefault();
                    alert('Enter at least two line items.');
                }
            });
        </script>
    @endif
@endsection