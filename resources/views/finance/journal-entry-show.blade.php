@extends('layouts.app')

@section('title', 'Journal Entry — '.$journalEntry->reference)

@section('content')
    <div class="view-head">
        <div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <h2 style="margin:0;">{{ $journalEntry->reference }}</h2>
                <span class="tag {{ journal_status_badge($journalEntry->status) }}">{{ journal_status_label($journalEntry->status) }}</span>
            </div>
            <p class="sub" style="margin-top:6px;">{{ $journalEntry->description }}</p>
            <p class="sub" style="margin-top:4px;font-size:13px;">
                <span>Entry date: <strong>{{ $journalEntry->entry_date->format('d M Y') }}</strong></span>
                <span style="margin:0 8px;opacity:.4;">·</span>
                <span>Created {{ $journalEntry->created_at->diffForHumans() }} by <strong>{{ $journalEntry->creator?->name ?? '—' }}</strong></span>
                @if($journalEntry->posted_at)
                    <span style="margin:0 8px;opacity:.4;">·</span>
                    <span>Posted {{ $journalEntry->posted_at->diffForHumans() }} by <strong>{{ $journalEntry->poster?->name ?? '—' }}</strong></span>
                @endif
            </p>
        </div>
        <div class="view-actions" style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('finance.journals.index') }}" class="btn btn-ghost">← Back to journal</a>
            <button class="btn btn-ghost" onclick="window.print()">Print</button>
            @if(is_admin() && $journalEntry->status === 'draft')
                <form method="POST" action="{{ route('finance.journals.post', $journalEntry) }}" onsubmit="return confirm('Post {{ $journalEntry->reference }}?')">
                    @csrf
                    <button class="btn btn-primary">Post entry</button>
                </form>
            @endif
            @if(is_admin() && $journalEntry->status === 'posted')
                <button type="button" class="btn btn-soft" style="background:var(--danger);color:#fff;border-color:var(--danger);" onclick="openReverseModalShow()">Reverse entry</button>
            @endif
        </div>
    </div>

    @if(is_admin() && $journalEntry->status === 'posted')
        <div class="modal-backdrop" id="reverseModalShow" onclick="if(event.target===this)closeModal('reverseModalShow')">
            <div class="modal" style="max-width:480px;">
                <div class="modal-head">
                    <h3>Reverse journal entry</h3>
                    <button type="button" class="modal-close" onclick="closeModal('reverseModalShow')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div style="display:flex;gap:12px;align-items:flex-start;background:var(--danger-100);border:1px solid #FFCDD2;border-radius:10px;padding:14px;">
                        <div style="width:36px;height:36px;border-radius:9px;background:var(--danger);color:#fff;display:flex;align-items:center;justify-content:center;flex:none;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
                        </div>
                        <div>
                            <div style="font-weight:700;color:var(--coffee-900);">{{ $journalEntry->reference }}</div>
                            <div class="cell-sub" style="margin-top:4px;">Reverse <strong>{{ $journalEntry->reference }}</strong> with an offsetting entry? This will create a reversal entry (RVS-…) and mark the original as reversed. This cannot be undone.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('reverseModalShow')">Cancel</button>
                    <form method="POST" action="{{ route('finance.journals.reverse', $journalEntry) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary" style="background:var(--danger);border-color:var(--danger);">Reverse entry</button>
                    </form>
                </div>
            </div>
        </div>
        <script>
            function openReverseModalShow() { openModal('reverseModalShow'); }
        </script>
    @endif

    @include('finance._nav')

    <div class="stat-grid">
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg></div></div>
            <div class="stat-value">@money($journalEntry->totalDebits())</div>
            <div class="stat-label">Total debits</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3"></path><circle cx="12" cy="12" r="10"></circle></svg></div></div>
            <div class="stat-value">@money($journalEntry->totalCredits())</div>
            <div class="stat-label">Total credits</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--terracotta-100);--stat-fg:var(--terracotta-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4l3 3"></path></svg></div></div>
            <div class="stat-value" style="color:{{ abs($journalEntry->totalDebits() - $journalEntry->totalCredits()) < 0.01 ? 'var(--acacia-600)' : 'var(--danger)' }};">
                @money(abs($journalEntry->totalDebits() - $journalEntry->totalCredits()))
            </div>
            <div class="stat-label">Difference @if(abs($journalEntry->totalDebits() - $journalEntry->totalCredits()) < 0.01) · Balanced @else · Unbalanced @endif</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--sand-200);--stat-fg:var(--coffee-700);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div></div>
            <div class="stat-value" style="font-size:15px;">{{ $journalEntry->lines->count() }} lines</div>
            <div class="stat-label">{{ $journalEntry->entry_date->format('l, j F Y') }} · {{ journal_status_label($journalEntry->status) }}</div>
        </div>
    </div>

    @if($transaction)
        <div class="table-card" style="border-left:4px solid var(--acacia-600);">
            <div class="table-toolbar" style="margin:-1px -1px 0;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="tag tag-green">Linked transaction</span>
                    <span class="cell-title">{{ $transaction->reference }}</span>
                    <span class="tag {{ status_badge($transaction->status) }}">{{ ucfirst($transaction->status) }}</span>
                </div>
                <a href="{{ route('transactions.receipt', $transaction) }}" class="btn btn-ghost btn-sm" style="margin-left:auto;">View receipt →</a>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;padding:16px 18px;">
                <div><div class="cell-sub" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Type</div><div class="cell-title">{{ txn_type_label($transaction->type) }}</div></div>
                <div><div class="cell-sub" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Amount</div><div class="cell-title">@money($transaction->amount)</div></div>
                <div><div class="cell-sub" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Fee</div><div class="cell-title">@money($transaction->fee)</div></div>
                <div><div class="cell-sub" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Commission</div><div class="cell-title">@money($transaction->commission)</div></div>
                <div><div class="cell-sub" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Customer</div><div class="cell-title">{{ $transaction->customer_name ?? '—' }}</div><div class="cell-sub">{{ $transaction->customer_phone }}</div></div>
                <div><div class="cell-sub" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Network</div><div class="cell-title"><span class="net-dot" style="background:{{ $transaction->network->color ?? '#A98968' }};"></span> {{ $transaction->network->name ?? '—' }}</div></div>
                <div><div class="cell-sub" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Date</div><div class="cell-title">{{ $transaction->created_at->format('d M Y H:i') }}</div><div class="cell-sub">{{ $transaction->created_at->diffForHumans() }}</div></div>
                <div><div class="cell-sub" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Provider ref</div><div class="cell-title" style="font-family:monospace;font-size:12px;">{{ $transaction->provider_reference ?? '—' }}</div></div>
            </div>
        </div>
    @endif

    @if($reversal)
        <div class="table-card" style="border-left:4px solid var(--danger);">
            <div style="padding:12px 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span class="tag tag-red">Reversed</span>
                <span class="cell-sub">This entry was reversed by <a href="{{ route('finance.journals.show', $reversal) }}" class="cell-title" style="color:var(--terracotta-600);text-decoration:none;">{{ $reversal->reference }}</a> on {{ $reversal->entry_date->format('d M Y') }}</span>
                <a href="{{ route('finance.journals.show', $reversal) }}" class="btn btn-ghost btn-sm" style="margin-left:auto;">View reversal →</a>
            </div>
        </div>
    @endif

    @if($original)
        <div class="table-card" style="border-left:4px solid var(--gold-500);">
            <div style="padding:12px 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span class="tag tag-gold">Reversal entry</span>
                <span class="cell-sub">This entry reverses <a href="{{ route('finance.journals.show', $original) }}" class="cell-title" style="color:var(--acacia-600);text-decoration:none;">{{ $original->reference }}</a></span>
                <a href="{{ route('finance.journals.show', $original) }}" class="btn btn-ghost btn-sm" style="margin-left:auto;">View original →</a>
            </div>
        </div>
    @endif

    <div class="table-card">
        <div class="table-toolbar">
            <strong style="font-size:14px;color:var(--coffee-900);">Entry lines</strong>
            <span class="cell-sub" style="margin-left:auto;">{{ $journalEntry->lines->count() }} lines · Double-entry must balance</span>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th style="width:44px;">#</th>
                        <th>Account</th>
                        <th>Description</th>
                        <th style="text-align:right;">Debit</th>
                        <th style="text-align:right;">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($journalEntry->lines as $idx => $line)
                        <tr>
                            <td class="cell-sub">{{ $idx + 1 }}</td>
                            <td>
                                <div class="cell-title">
                                    <span class="tag" style="font-family:monospace;font-size:11px;background:var(--sand-100);color:var(--coffee-700);border:1px solid var(--line);">{{ $line->account->code ?? '—' }}</span>
                                    <span style="margin-left:6px;">{{ $line->account->name ?? 'Unknown account' }}</span>
                                </div>
                                <div class="cell-sub" style="font-size:11px;text-transform:uppercase;letter-spacing:.03em;">{{ $line->account->type ?? '' }}</div>
                            </td>
                            <td class="cell-sub">{{ $line->description ?: '—' }}</td>
                            <td style="text-align:right;" class="cell-title">
                                @if((float)$line->debit > 0)
                                    @money($line->debit)
                                @else
                                    <span class="cell-sub">—</span>
                                @endif
                            </td>
                            <td style="text-align:right;" class="cell-title">
                                @if((float)$line->credit > 0)
                                    @money($line->credit)
                                @else
                                    <span class="cell-sub">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:var(--sand-100);font-weight:700;">
                        <td colspan="3" style="text-align:right;" class="cell-title">Totals</td>
                        <td style="text-align:right;" class="cell-title">@money($journalEntry->totalDebits())</td>
                        <td style="text-align:right;" class="cell-title">@money($journalEntry->totalCredits())</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="panel-grid" style="margin-top:18px;">
        <div class="panel">
            <div class="panel-head"><h3>Entry details</h3></div>
            <div class="panel-body">
                <div class="receipt">
                    <div class="receipt-row"><span>Reference</span><b style="font-family:monospace;">{{ $journalEntry->reference }}</b></div>
                    <div class="receipt-row"><span>Description</span><b>{{ $journalEntry->description }}</b></div>
                    <div class="receipt-row"><span>Entry date</span><b>{{ $journalEntry->entry_date->format('l, j F Y') }}</b></div>
                    <div class="receipt-row"><span>Status</span><b><span class="tag {{ journal_status_badge($journalEntry->status) }}">{{ journal_status_label($journalEntry->status) }}</span></b></div>
                    <div class="receipt-row"><span>Created by</span><b>{{ $journalEntry->creator?->name ?? 'System' }}</b><span class="cell-sub" style="font-weight:400;">{{ $journalEntry->created_at->format('d M Y H:i') }} ({{ $journalEntry->created_at->diffForHumans() }})</span></div>
                    <div class="receipt-row"><span>Posted by</span><b>{{ $journalEntry->poster?->name ?? '—' }}</b><span class="cell-sub" style="font-weight:400;">@if($journalEntry->posted_at){{ $journalEntry->posted_at->format('d M Y H:i') }} ({{ $journalEntry->posted_at->diffForHumans() }}) @else — @endif</span></div>
                    <div class="receipt-row"><span>Lines</span><b>{{ $journalEntry->lines->count() }}</b></div>
                    <div class="receipt-row"><span>Balanced</span><b style="color:{{ abs($journalEntry->totalDebits() - $journalEntry->totalCredits()) < 0.01 ? 'var(--acacia-600)' : 'var(--danger)' }};">{{ abs($journalEntry->totalDebits() - $journalEntry->totalCredits()) < 0.01 ? 'Yes' : 'No — '.money(abs($journalEntry->totalDebits() - $journalEntry->totalCredits())).' difference' }}</b></div>
                </div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-head"><h3>Audit &amp; links</h3></div>
            <div class="panel-body">
                <div class="receipt">
                    <div class="receipt-row"><span>Entry ID</span><b>#{{ $journalEntry->id }}</b></div>
                    <div class="receipt-row"><span>Journal URL</span><b><a href="{{ route('finance.journals.show', $journalEntry) }}" style="color:var(--terracotta-600);word-break:break-all;">{{ route('finance.journals.show', $journalEntry) }}</a></b></div>
                    @if($transaction)
                        <div class="receipt-row"><span>Transaction</span><b><a href="{{ route('transactions.receipt', $transaction) }}" style="color:var(--acacia-600);">{{ $transaction->reference }}</a></b></div>
                    @endif
                    @if($reversal)
                        <div class="receipt-row"><span>Reversal entry</span><b><a href="{{ route('finance.journals.show', $reversal) }}" style="color:var(--danger);">{{ $reversal->reference }}</a> ({{ $reversal->entry_date->format('d M Y') }})</b></div>
                    @endif
                    @if($original)
                        <div class="receipt-row"><span>Original entry</span><b><a href="{{ route('finance.journals.show', $original) }}" style="color:var(--coffee-700);">{{ $original->reference }}</a></b></div>
                    @endif
                    <div class="receipt-row"><span>General ledger</span><b><a href="{{ route('finance.ledger.index') }}" style="color:var(--coffee-700);">View GL →</a></b></div>
                </div>
            </div>
        </div>
    </div>
@endsection
