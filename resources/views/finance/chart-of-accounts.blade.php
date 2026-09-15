@extends('layouts.app')

@section('title', 'Chart of Accounts')

@section('content')
    <div class="view-head">
        <div>
            <h2>Chart of Accounts</h2>
            <p class="sub">The financial structure. Every journal entry posts to an account here.</p>
        </div>
        <div class="view-actions">
            <span class="tag tag-grey">@money($totals->sum()) accounts</span>
            @if (is_admin())
                <button class="btn btn-primary" onclick="openAccountDrawer()">New account</button>
            @endif
        </div>
    </div>

    @include('finance._nav')

    <div class="balance-strip">
        @foreach (['asset', 'liability', 'equity', 'income', 'expense'] as $type)
            <div class="balance-box">
                <div class="bb-label">{{ account_type_label($type) }} accounts</div>
                <div class="bb-amount">{{ $totals->get($type, 0) }}</div>
                <div class="bb-sub">{{ ucfirst($type) }}s on the books</div>
            </div>
        @endforeach
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <span style="font-weight:700;color:var(--coffee-900);">Accounts ({{ $tree->count() }})</span>
            <div class="table-search" style="margin-left:auto;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" placeholder="Search accounts…" oninput="filterRows(this.value)">
            </div>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Normal</th>
                        <th>Status</th>
                        <th>Journal lines</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="accountBody">
                    @forelse ($tree as $node)
                        @include('finance._account-row', ['node' => $node])
                    @empty
                        <tr><td colspan="7" class="empty-state">No accounts yet. Create the first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if (is_admin())
        <div class="modal-backdrop" id="accountDrawerBackdrop" onclick="if(event.target===this)closeAccountDrawer()">
            <div class="modal">
                <div class="modal-head">
                    <h3 id="accountDrawerTitle">New account</h3>
                    <button type="button" class="modal-close" onclick="closeAccountDrawer()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <form id="accountDrawerForm" method="POST" class="modal-body">
                    @csrf
                    <input type="hidden" name="_method" value="POST">
                    <div class="field">
                        <label for="code">Code</label>
                        <input type="text" id="code" name="code" placeholder="e.g. 1200" maxlength="20" required>
                    </div>
                    <div class="field">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" placeholder="e.g. Mobile Money Float" maxlength="150" required>
                    </div>
                    <div class="field">
                        <label for="type">Type</label>
                        <select id="type" name="type" required>
                            <option value="">Select a type…</option>
                            @foreach (['asset', 'liability', 'equity', 'income', 'expense'] as $type)
                                <option value="{{ $type }}">{{ account_type_label($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="parent_id">Parent account</label>
                        <select id="parent_id" name="parent_id">
                            <option value="">None (top level)</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="2" maxlength="255" placeholder="Optional…"></textarea>
                    </div>
                    <div class="modal-foot" style="padding:18px 0 0;">
                        <button type="button" class="btn btn-ghost" onclick="closeAccountDrawer()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save account</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        function filterRows(q) {
            q = q.toLowerCase();
            document.querySelectorAll('#accountBody tr[data-search]').forEach(tr => {
                tr.style.display = (!q || tr.dataset.search.includes(q)) ? 'table-row' : 'none';
            });
        }

        @if (is_admin())
            function openAccountDrawer() {
                const form = document.getElementById('accountDrawerForm');
                form.reset();
                form.querySelector('[name="code"]').disabled = false;
                form.querySelector('input[name="_method"]').value = 'POST';
                form.action = '{{ route('finance.accounts.store') }}';
                document.getElementById('accountDrawerTitle').textContent = 'New account';
                document.getElementById('accountDrawerBackdrop').classList.add('show');
            }

            function editAccount(id, code, name, type, parent, description) {
                const form = document.getElementById('accountDrawerForm');
                form.reset();
                const method = form.querySelector('input[name="_method"]');
                method.value = 'PUT';
                form.action = '{{ route('finance.accounts.update', ['account' => '__ACCOUNT__']) }}'.replace('__ACCOUNT__', id);
                form.querySelector('[name="code"]').value = code;
                form.querySelector('[name="code"]').disabled = true;
                form.querySelector('[name="name"]').value = name;
                form.querySelector('[name="type"]').value = type;
                form.querySelector('[name="parent_id"]').value = parent;
                form.querySelector('[name="description"]').value = description || '';
                document.getElementById('accountDrawerTitle').textContent = 'Edit ' + code;
                document.getElementById('accountDrawerBackdrop').classList.add('show');
            }

            function closeAccountDrawer() {
                document.getElementById('accountDrawerBackdrop').classList.remove('show');
            }

            function deleteAccount(id, code) {
                if (!confirm('Delete account ' + code + '? This cannot be undone.')) return;
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('finance.accounts.destroy', ['account' => '__ACCOUNT__']) }}'.replace('__ACCOUNT__', id);
                form.innerHTML = '@csrf<input type="hidden" name="_method" value="DELETE">';
                document.body.appendChild(form);
                form.submit();
            }
        @endif
    </script>
@endsection