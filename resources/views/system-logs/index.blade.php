@extends('layouts.app')

@section('title', 'System Logs')

@section('content')
    <style>
        .log-msg{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12.5px;line-height:1.5;color:var(--coffee-900);word-break:break-word;max-width:640px;}
        .log-trace{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:11.5px;line-height:1.55;color:#4a3b2c;background:var(--sand-100);border-radius:8px;padding:10px 12px;margin-top:8px;max-height:260px;overflow:auto;white-space:pre-wrap;word-break:break-all;}
        .log-meta{display:flex;flex-wrap:wrap;gap:8px 18px;margin-top:3px;}
        .log-meta span{font-size:11.5px;color:var(--ink-soft);}
        .log-lv{white-space:nowrap;}
        .log-lv-DEBUG{background:var(--sand-200);color:var(--coffee-700);}
        .log-lv-INFO{background:var(--acacia-100);color:var(--acacia-600);}
        .log-lv-NOTICE{background:#e3ecf5;color:#2f5d80;}
        .log-lv-WARNING{background:var(--gold-100);color:#8a6418;}
        .log-lv-ERROR,.log-lv-CRITICAL,.log-lv-ALERT,.log-lv-EMERGENCY{background:var(--danger-100);color:var(--danger);}
        .log-summary{display:flex;flex-wrap:wrap;gap:14px;align-items:center;padding:12px 18px;border-bottom:1px solid var(--line);font-size:12.5px;color:var(--ink-soft);}
        .log-summary b{color:var(--coffee-900);font-weight:700;}
        .log-warn{background:var(--gold-100);color:#8a6418;border:1px solid var(--gold-500);border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:14px;}
        .log-lvchips{display:flex;flex-wrap:wrap;gap:6px;}
    </style>

    <div class="view-head">
        <div>
            <h2>System Logs</h2>
            <p class="sub">Read-only view of <code>storage/logs</code> for diagnosing application errors.</p>
        </div>
        <div class="view-actions">
            <a class="btn btn-ghost" href="{{ route('system.logs') }}">Reset filters</a>
        </div>
    </div>

    <div style="background:var(--terracotta-100);color:var(--terracotta-600);border:1px solid var(--terracotta-500);border-radius:14px;padding:14px 18px;margin-bottom:18px;font-size:13px;">
        Log entries can contain sensitive values such as SQL bindings, phone numbers and file paths, so this page is restricted to administrators. Everything shown here is read-only — the log is never modified from this page.
    </div>

    <form method="GET" action="{{ route('system.logs') }}">
        <div class="table-card">
            <div class="table-toolbar">
                <div class="chip-filters">
                    <span class="link" style="font-size:13px;color:var(--ink-soft);font-weight:600;">File:</span>
                    <select name="file" onchange="this.form.submit()" style="padding:9px 12px;border:1.5px solid var(--line);border-radius:10px;font-size:13px;background:var(--white);color:var(--coffee-700);font-weight:600;">
                        @forelse ($files as $file)
                            <option value="{{ $file['name'] }}" {{ $selectedFile === $file['name'] ? 'selected' : '' }}>
                                {{ $file['label'] }} ({{ $file['name'] }})
                            </option>
                        @empty
                            <option value="">No log files found</option>
                        @endforelse
                    </select>

                    <span class="link" style="font-size:13px;color:var(--ink-soft);font-weight:600;margin-left:8px;">Level:</span>
                    <select name="level" onchange="this.form.submit()" style="padding:9px 12px;border:1.5px solid var(--line);border-radius:10px;font-size:13px;background:var(--white);color:var(--coffee-700);font-weight:600;">
                        <option value="">All levels</option>
                        @foreach ($availableLevels as $levelOption)
                            @continue(($levelCounts[$levelOption] ?? 0) === 0 && $selectedLevel !== $levelOption)
                            <option value="{{ $levelOption }}" {{ $selectedLevel === $levelOption ? 'selected' : '' }}>
                                {{ $levelOption }} ({{ $levelCounts[$levelOption] ?? 0 }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="table-search" style="margin-left:auto;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search message, context or trace…">
                </div>

                <button type="submit" class="btn btn-primary btn-sm">Search</button>
            </div>

            @if ($files->isEmpty())
                <div class="log-summary">No readable log files were found in <code>storage/logs</code>.</div>
            @else
                <div class="log-summary">
                    <span><b>{{ $logs->total() }}</b> {{ $logs->total() === 1 ? 'entry' : 'entries' }} shown</span>
                    @if ($selectedLevel)
                        <span>Level <b>{{ $selectedLevel }}</b></span>
                    @endif
                    @if ($search !== '')
                        <span>Matching <b>&ldquo;{{ $search }}&rdquo;</b></span>
                    @endif
                    @if ($fileMeta)
                        <span>File size <b>{{ number_format($fileMeta['size'] / 1024, 1) }} KB</b></span>
                        <span>Last written <b>{{ $fileMeta['modified']?->diffForHumans() ?? 'unknown' }}</b></span>
                    @endif
                    <span>Parsed <b>{{ $totalScanned }}</b> most recent entries from the tail of the file</span>
                </div>
            @endif
        </div>
    </form>

    <div class="table-card" style="margin-top:-24px;">
        <div class="table-scroll">
            <table style="min-width:720px;">
                <thead>
                    <tr>
                        <th style="width:150px;">When</th>
                        <th style="width:110px;">Level</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody id="logRows">
                    @forelse ($logs as $entry)
                        <tr data-index="{{ $loop->index }}"
                            data-time="{{ $entry['time'] }}"
                            data-level="{{ $entry['level'] }}"
                            data-message="{{ $entry['message'] }}"
                            data-exception="{{ $entry['exception'] ?? '' }}"
                            data-context="{{ json_encode($entry['context']) }}"
                            data-trace="{{ json_encode($entry['trace']) }}"
                            style="cursor:pointer;">
                            <td>
                                <div class="cell-title">{{ \Illuminate\Support\Str::limit($entry['time'], 16, '') }}</div>
                                <div class="cell-sub">{{ $entry['timestamp']?->diffForHumans() ?? '' }}</div>
                            </td>
                            <td>
                                <span class="tag log-lv log-lv-{{ $entry['level'] }}">{{ $entry['level'] }}</span>
                            </td>
                            <td>
                                <div class="log-msg">{{ \Illuminate\Support\Str::limit($entry['message'], 220) }}</div>
                                <div class="log-meta">
                                    <span>env: {{ $entry['env'] }}</span>
                                    @if ($entry['exception'])
                                        <span>{{ \Illuminate\Support\Str::limit($entry['exception'], 90) }}</span>
                                    @endif
                                    @if ($entry['trace'])
                                        <span>{{ count($entry['trace']) }} stack frames</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="empty-state">
                                <h4>Nothing to show</h4>
                                <p>
                                    @if ($files->isEmpty())
                                        No log files are present in storage/logs yet.
                                    @elseif ($search !== '' || $selectedLevel)
                                        No entries match the current filters.
                                    @else
                                        This log file is empty.
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->links() }}
    </div>

    <div class="modal-backdrop" id="logEntryModal">
        <style>
            #logEntryModal .receipt{max-height:340px;overflow:auto;}
            #logEntryModal .receipt-row{display:block;}
            #logEntryModal .receipt-row span{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-soft);font-weight:700;margin-bottom:3px;}
            #logEntryModal .receipt-row b{display:block;white-space:pre-wrap;word-break:break-word;font-weight:600;}
        </style>
        <div class="modal">
            <div class="modal-head">
                <h4 id="logEntryTitle">Log entry</h4>
                <button type="button" class="modal-close" onclick="closeModal('logEntryModal')">&times;</button>
            </div>
            <div class="modal-body" id="logEntryBody"></div>
            <div class="modal-foot">
                <button type="button" class="btn btn-primary" onclick="closeModal('logEntryModal')">Close</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        @php
            $entries = $logs->getCollection()->map(fn (array $entry): array => [
                'time' => $entry['time'],
                'level' => $entry['level'],
                'env' => $entry['env'],
                'message' => $entry['message'],
                'exception' => $entry['exception'],
                'context' => $entry['context'] === [] ? null : json_encode($entry['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'trace' => $entry['trace'] === [] ? null : implode("\n", $entry['trace']),
                'raw' => $entry['raw'],
            ])->values();
        @endphp
        const sysLogEntries = @json($entries);

        document.querySelectorAll('#logRows tr[data-index]').forEach(row => {
            row.addEventListener('click', () => {
                const entry = sysLogEntries[Number(row.dataset.index)];
                if (!entry) return;

                const rows = [
                    ['When', entry.time],
                    ['Level', entry.level],
                    ['Environment', entry.env],
                    ['Message', entry.message],
                    ['Exception', entry.exception || '—'],
                    ['Context', entry.context || '—'],
                    ['Stack trace', entry.trace || '—'],
                ];

                const body = document.getElementById('logEntryBody');
                body.innerHTML = '';

                const receipt = document.createElement('div');
                receipt.className = 'receipt';

                rows.forEach(([label, value]) => {
                    const r = document.createElement('div');
                    r.className = 'receipt-row';
                    const s = document.createElement('span');
                    s.textContent = label;
                    const b = document.createElement('b');
                    b.textContent = value;
                    if (label === 'Stack trace' && entry.trace) {
                        b.className = 'log-trace';
                    }
                    r.appendChild(s);
                    r.appendChild(b);
                    receipt.appendChild(r);
                });

                body.appendChild(receipt);
                document.getElementById('logEntryTitle').textContent = entry.level + ' · ' + entry.time;
                openModal('logEntryModal');
            });
        });
    </script>
@endsection