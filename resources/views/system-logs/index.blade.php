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
            #logEntryModal .log-cols{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.25fr);gap:18px;align-items:start;}
            #logEntryModal .log-col{min-width:0;}
            #logEntryModal .log-col-title{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-soft);font-weight:700;margin-bottom:8px;}
            #logEntryModal .detail-list{display:flex;flex-direction:column;gap:8px;}
            #logEntryModal .detail-row{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;padding:9px 0;border-bottom:1px solid var(--line);}
            #logEntryModal .detail-row:last-child{border-bottom:none;}
            #logEntryModal .dk{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-soft);font-weight:700;flex:none;}
            #logEntryModal .dv{font-size:13.5px;color:var(--coffee-900);font-weight:600;text-align:right;word-break:break-word;}
            #logEntryModal .dv-wrap{text-align:left;}
            #logEntryModal .log-trace{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:11.5px;line-height:1.55;color:#4a3b2c;background:var(--sand-100);border-radius:8px;padding:10px 12px;margin-top:8px;max-height:300px;overflow:auto;white-space:pre-wrap;word-break:break-all;text-align:left;}
            @media (max-width:640px){
                #logEntryModal .log-cols{grid-template-columns:1fr;gap:14px;}
            }
        </style>
        <div class="popup" style="max-width:760px; width:100%; margin:auto;">
            <div class="modal-head">
                <h3>Log entry</h3>
                <button class="modal-close" onclick="closeModal('logEntryModal')">✕</button>
            </div>
            <div class="modal-body">
                <div class="log-cols">
                    <div class="log-col">
                        <div class="log-col-title">Entry</div>
                        <div class="detail-list">
                            <div class="detail-row"><span class="dk">When</span><span class="dv" id="logModalWhen">—</span></div>
                            <div class="detail-row"><span class="dk">Level</span><span class="dv"><span class="tag log-lv" id="logModalLevel">—</span></span></div>
                            <div class="detail-row"><span class="dk">Environment</span><span class="dv" id="logModalEnv">—</span></div>
                            <div class="detail-row"><span class="dk">Frames</span><span class="dv" id="logModalFrames">—</span></div>
                            <div class="detail-row" style="flex-direction:column;align-items:stretch;gap:6px;">
                                <span class="dk">Message</span>
                                <div class="log-msg" id="logModalMessage" style="max-width:none;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="log-col">
                        <div class="log-col-title">Context &amp; trace</div>
                        <div class="detail-list">
                            <div class="detail-row" style="flex-direction:column;align-items:stretch;gap:6px;">
                                <span class="dk">Context</span>
                                <div class="dv dv-wrap" id="logModalContext" style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;">—</div>
                                <div class="cell-sub" id="logModalContextNote" style="color:var(--danger);"></div>
                            </div>
                            <div class="detail-row" style="flex-direction:column;align-items:stretch;gap:6px;">
                                <span class="dk">Stack trace</span>
                                <div id="logModalTraceWrap" style="display:none;">
                                    <div class="log-trace" id="logModalTrace"></div>
                                </div>
                                <div class="dv" id="logModalTraceEmpty" style="font-weight:500;color:var(--ink-soft);">—</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-primary" onclick="closeModal('logEntryModal')">Close</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        @php
            // Only the fields the popup needs, so the page stays light even when
            // a record carries a long stack trace.
            $entries = $logs->getCollection()->map(fn (array $entry): array => [
                'time' => $entry['time'],
                'level' => $entry['level'],
                'env' => $entry['env'],
                'message' => $entry['message'],
                'context' => $entry['context'] === [] ? null : json_encode($entry['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'contextTruncated' => (bool) $entry['contextTruncated'],
                'trace' => $entry['trace'] === [] ? null : implode("\n", $entry['trace']),
            ])->values();
        @endphp
        const sysLogEntries = @json($entries);

        function openLogModal(tr) {
            const entry = sysLogEntries[Number(tr.dataset.index)];
            if (!entry) return;

            document.getElementById('logModalWhen').textContent = entry.time;

            const level = document.getElementById('logModalLevel');
            level.textContent = entry.level;
            level.className = 'tag log-lv log-lv-' + entry.level;

            document.getElementById('logModalEnv').textContent = entry.env;

            const frames = entry.trace ? entry.trace.split('\n').length : 0;
            document.getElementById('logModalFrames').textContent = frames ? frames + (frames === 1 ? ' frame' : ' frames') : '—';

            document.getElementById('logModalMessage').textContent = entry.message || '—';

            const context = document.getElementById('logModalContext');
            context.textContent = entry.context || '—';
            context.style.color = entry.contextTruncated ? 'var(--danger)' : '';
            document.getElementById('logModalContextNote').textContent = entry.contextTruncated
                ? 'Context was cut short by the logger — some values may be missing.'
                : '';

            const trace = document.getElementById('logModalTrace');
            const wrap = document.getElementById('logModalTraceWrap');
            const empty = document.getElementById('logModalTraceEmpty');

            if (entry.trace) {
                trace.textContent = entry.trace;
                wrap.style.display = 'block';
                empty.style.display = 'none';
            } else {
                trace.textContent = '';
                wrap.style.display = 'none';
                empty.style.display = 'block';
            }

            openModal('logEntryModal');
        }

        document.querySelectorAll('#logRows tr[data-index]').forEach(tr => {
            tr.style.cursor = 'pointer';
            tr.addEventListener('click', () => openLogModal(tr));
        });
    </script>
@endsection