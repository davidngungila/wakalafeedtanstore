@extends('layouts.app')

@section('title', 'Networks')

@section('content')
    <div class="view-head">
        <div>
            <h2>Mobile Money Networks</h2>
            <p class="sub">View supported networks and commission rates; administrators manage them.</p>
        </div>
        <div class="view-actions">
            @if (is_admin())
                <button class="btn btn-primary" onclick="openNetworkModal()">+ Add network</button>
            @endif
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><circle cx="5" cy="5" r="2"></circle><circle cx="19" cy="5" r="2"></circle><circle cx="5" cy="19" r="2"></circle><circle cx="19" cy="19" r="2"></circle><path d="M6.9 6.5 10 9m7.1-2.5L14 9m-7.1 9.5L10 15m7.1 2.5L14 15"></path></svg></div><span class="stat-trend up">{{ count($networks) }} connected</span></div>
            <div class="stat-value">{{ count($networks) }}</div>
            <div class="stat-label">Networks</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg></div></div>
            <div class="stat-value">@money($totalFloat)</div>
            <div class="stat-label">Total float across networks</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--terracotta-100);--stat-fg:var(--terracotta-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg></div></div>
            <div class="stat-value">{{ $networks->sum('transactions_count') }}</div>
            <div class="stat-label">Total transactions</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--danger-100);--stat-fg:var(--danger);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path></svg></div></div>
            <div class="stat-value">@money($networks->sum('commission'))</div>
            <div class="stat-label">Earned commission</div>
        </div>
    </div>

    <div class="card-grid" style="margin-bottom:24px;">
        @foreach ($networks as $network)
            <div class="mini-card">
                <div class="mc-top">
                    <div class="mc-name"><span class="net-dot" style="background:{{ $network['color'] }};"></span>{{ $network['name'] }}</div>
                    @if (is_admin())
                        <button title="Edit network" onclick="editNetwork({{ $network['id'] }})" style="width:30px;height:30px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"></path></svg>
                        </button>
                    @endif
                </div>
                <div class="mc-big">@money($network['float'])</div>
                <div class="mc-label">Float balance</div>
                <div class="kpi-row">
                    <div class="kpi-item"><b>{{ $network['transactions_count'] }}</b><span>Transactions</span></div>
                    <div class="kpi-item"><b>@money($network['volume'])</b><span>Volume</span></div>
                    <div class="kpi-item"><b>@money($network['commission'])</b><span>Commission</span></div>
                </div>
                <div class="kpi-row" style="margin-top:10px;">
                    <span class="tag {{ $network['is_active'] ? 'tag-green' : 'tag-grey' }}">{{ $network['is_active'] ? 'API connected' : 'Suspended' }}</span>
                    <span class="tag tag-terracotta" style="margin-left:auto;">{{ $network['code'] }}</span>
                </div>
                <a href="{{ route('networks.show', $network['route_key']) }}" style="display:flex;align-items:center;justify-content:space-between;margin-top:14px;padding:10px 12px;border:1.5px solid var(--line);border-radius:9px;font-size:12.5px;font-weight:700;color:var(--terracotta-600);background:var(--sand-50);">
                    View all transactions
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
            </div>
        @endforeach
    </div>

    <form method="GET" action="{{ route('networks.index') }}">
        <div class="table-card" style="margin-bottom:18px;">
            <div class="table-head" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;border-bottom:1px solid var(--line);flex-wrap:wrap;">
                <h3 style="font-size:16.5px;">Commission rates</h3>
                <div class="chip-filters">
                    <button type="button" class="chip {{ $activeType === 'all' ? 'active' : '' }}" data-type="all" onclick="setRateFilter('all')">All</button>
                    @foreach ($types as $type)
                        <button type="button" class="chip {{ $activeType === $type ? 'active' : '' }}" data-type="{{ $type }}" onclick="setRateFilter('{{ $type }}')">{{ txn_type_label($type) }}</button>
                    @endforeach
                    <input type="hidden" name="type" id="fType" value="{{ $activeType }}">
                </div>
            </div>
        </div>
    </form>

    @if (is_admin())
        <form method="POST" action="{{ route('networks.updateRates') }}" data-rates-form>
            @csrf
    @endif
        <div class="table-card">
            <div class="table-scroll">
                <table style="min-width:640px;">
                    <thead>
                        <tr>
                            <th>Network</th>
                            <th>Type</th>
                            <th>Rate</th>
                            <th>Structure</th>
                            <th>Enabled</th>
                        </tr>
                    </thead>
                    <tbody id="rateRows">
                        @forelse ($rates as $rate)
                            <tr data-network="{{ $rate->network?->name }}"
                                data-color="{{ $rate->network?->color }}"
                                data-type="{{ txn_type_label($rate->transaction_type) }}"
                                data-rawtype="{{ $rate->transaction_type }}"
                                data-rate="{{ $rate->rate }}"
                                data-structure="{{ $rate->rate_structure }}"
                                data-active="{{ $rate->is_active ? '1' : '0' }}">
                                <td>
                                    <span class="net-dot" style="background:{{ $rate->network?->color }};"></span>
                                    {{ $rate->network?->name }}
                                </td>
                                <td>
                                    <div class="cell-title">{{ txn_type_label($rate->transaction_type) }}</div>
                                    <div class="cell-sub">{{ $rate->transaction_type }}</div>
                                </td>
                                <td style="width:180px;">
                                    @if (is_admin())
                                        <input type="hidden" name="rates[{{ $rate->id }}][id]" value="{{ $rate->id }}">
                                        <input type="number" name="rates[{{ $rate->id }}][rate]" step="any" min="0" value="{{ $rate->rate }}" style="width:100%;padding:9px 12px;border:1.5px solid var(--line);border-radius:9px;font-size:14px;font-weight:600;color:var(--coffee-900);">
                                    @else
                                        <span class="cell-title">{{ number_format($rate->rate, 2) }}%</span>
                                    @endif
                                </td>
                                <td class="cell-sub">{{ $rate->rate_structure }} (% of amount)</td>
                                <td>
                                    @if (is_admin())
                                        <select name="rates[{{ $rate->id }}][is_active]" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;font-weight:600;background:var(--white);color:var(--coffee-700);">
                                            <option value="1" {{ $rate->is_active ? 'selected' : '' }}>Active</option>
                                            <option value="0" {{ ! $rate->is_active ? 'selected' : '' }}>Disabled</option>
                                        </select>
                                    @else
                                        <span class="tag {{ $rate->is_active ? 'tag-green' : 'tag-grey' }}">{{ $rate->is_active ? 'Active' : 'Disabled' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty-state"><h4>No commission rates</h4><p>Select another type or add a network.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="modal-foot" style="border:none;padding:16px 18px;">
                @if (is_admin())
                    <button type="submit" class="btn btn-primary">Save all rates</button>
                @endif
            </div>
        </div>
    @if (is_admin())
        </form>
    @endif

    <!-- Add / edit network modal -->
    <div class="modal-backdrop" id="networkModal">
        <div class="modal" style="max-width:480px;">
            <div class="modal-head">
                <h3 id="networkModalTitle">Add network</h3>
                <button class="modal-close" onclick="closeModal('networkModal')">✕</button>
            </div>
            <form id="networkForm" data-network-form>
                <input type="hidden" name="_method" value="" id="networkMethod">
                <input type="hidden" name="id" id="networkId">
                <div class="modal-body">
                    <div class="field">
                        <label>Network name</label>
                        <input type="text" name="name" id="networkName" placeholder="e.g. Tigo" required>
                    </div>
                    <div class="field">
                        <label>Code</label>
                        <input type="text" name="code" id="networkCode" placeholder="e.g. TIGO" required>
                    </div>
                    <div class="field">
                        <label>Brand color</label>
                        <select name="color" id="networkColor">
                            <option value="#C2592B">Terracotta</option>
                            <option value="#D4A24C">Gold</option>
                            <option value="#5E6E3F">Acacia</option>
                            <option value="#E60000">Vodacom red</option>
                            <option value="#D31145">Airtel red</option>
                            <option value="#114999">Mixx blue</option>
                            <option value="#F58220">Halo orange</option>
                            <option value="#6B5A48">Neutral</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Status</label>
                        <select name="is_active">
                            <option value="1">Active (API connected)</option>
                            <option value="0">Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('networkModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="networkSubmitBtn">Save network</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const networksData = @json($networks);

        function setRateFilter(type) {
            document.getElementById('fType').value = type;
            document.getElementById('fType').closest('form').submit();
        }

        function openNetworkModal(id = null) {
            const form = document.getElementById('networkForm');
            if (id) {
                const n = networksData.find(x => Number(x.id) === Number(id));
                if (!n) return;
                document.getElementById('networkModalTitle').textContent = 'Edit network';
                document.getElementById('networkMethod').value = 'PUT';
                document.getElementById('networkId').value = n.id;
                form.action = `/networks/${n.route_key}`;
                document.getElementById('networkName').value = n.name;
                document.getElementById('networkCode').value = n.code;
                document.getElementById('networkColor').value = n.color || '#C2592B';
                form.querySelector('[name="is_active"]').value = n.is_active ? '1' : '0';
                document.getElementById('networkSubmitBtn').textContent = 'Update network';
            } else {
                document.getElementById('networkModalTitle').textContent = 'Add network';
                document.getElementById('networkMethod').value = '';
                document.getElementById('networkId').value = '';
                form.action = '{{ route("networks.store") }}';
                document.getElementById('networkName').value = '';
                document.getElementById('networkCode').value = '';
                document.getElementById('networkColor').value = '#C2592B';
                form.querySelector('[name="is_active"]').value = '1';
                document.getElementById('networkSubmitBtn').textContent = 'Save network';
            }
            openModal('networkModal');
        }

        function editNetwork(id) { openNetworkModal(id); }

        document.querySelectorAll('[data-network-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const method = document.getElementById('networkMethod').value || 'POST';
                submitForm(form, { method, done: () => setTimeout(() => location.reload(), 600) });
            });
        });

        document.querySelectorAll('[data-rates-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: () => toast('Rates saved offline', 'success') });
            });
        });

        bindRowClick('#rateRows tr[data-rawtype]', tr => {
            return [
                ['Network', tr.dataset.network ? { __html: `<span class="net-dot" style="background:${tr.dataset.color || '#999'};"></span> ${tr.dataset.network}` } : '—'],
                ['Type', tr.dataset.type],
                ['Type code', tr.dataset.rawtype],
                ['Rate', Number(tr.dataset.rate).toLocaleString('en-US', { maximumFractionDigits: 2 }) + '%'],
                ['Structure', tr.dataset.structure + ' (% of amount)'],
                ['Enabled', { __html: tr.dataset.active === '1' ? '<span class="tag tag-green">Active</span>' : '<span class="tag tag-grey">Disabled</span>' }],
            ];
        }, 'Commission rate');
    </script>
@endsection