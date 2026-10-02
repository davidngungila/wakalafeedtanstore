@extends('layouts.app')

@section('title', 'Users & Roles')

@section('content')
    <div class="view-head">
        <div>
            <h2>Users &amp; Roles</h2>
            <p class="sub">Cashiers run daily operations, supervisors monitor and approve, administrators manage the system.</p>
        </div>
        <div class="view-actions">
            <a class="btn btn-ghost" href="{{ route('users.sessions') }}">View all active sessions</a>
            <a class="btn btn-primary" href="{{ route('users.create') }}">+ Add user</a>
        </div>
        @include('exports._export-modal', ['route' => $exportRoute, 'columns' => $exportColumns, 'title' => 'Users'])
    </div>

    <form method="GET" action="{{ route('users.index') }}">
        <div class="table-card">
            <div class="table-toolbar">
                <div class="chip-filters" id="roleChips">
                    <button type="button" class="chip {{ $activeRole === 'all' ? 'active' : '' }}" data-role="all" onclick="setRoleFilter('all')">All</button>
                    <button type="button" class="chip {{ $activeRole === 'cashier' ? 'active' : '' }}" data-role="cashier" onclick="setRoleFilter('cashier')">Cashiers</button>
                    <button type="button" class="chip {{ $activeRole === 'supervisor' ? 'active' : '' }}" data-role="supervisor" onclick="setRoleFilter('supervisor')">Supervisors</button>
                    <button type="button" class="chip {{ $activeRole === 'admin' ? 'active' : '' }}" data-role="admin" onclick="setRoleFilter('admin')">Admins</button>
                    <input type="hidden" name="role" id="fRole" value="{{ $activeRole }}">
                </div>
                <div class="table-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" placeholder="Search users…" oninput="filterUserRows(this.value)">
                </div>
            </div>
        </div>
    </form>

    <div class="table-card" style="margin-top:-24px;">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Cash point</th>
                        <th>Phone</th>
                        <th>Two-factor</th>
                        <th>Status</th>
                        <th>Last login</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="usersBody">
                    @forelse ($users as $user)
                        <tr data-id="{{ $user->id }}" data-name="{{ strtolower($user->name) }}" data-role="{{ $user->role }}"
                            data-fullname="{{ $user->name }}"
                            data-email="{{ $user->email }}" data-phone="{{ $user->phone ?? '' }}"
                            data-agent="{{ $user->agent?->name ?? '' }}" data-agentcode="{{ $user->agent?->code ?? '' }}"
                            data-active="{{ $user->is_active ? '1' : '0' }}"
                            data-lastlogin="{{ $user->last_login_at?->format('d M Y H:i') ?? '' }}">
                            <td>
                                <div class="cell-main">
                                    <div class="avatar {{ $user->role === 'admin' ? 'gold' : ($user->role === 'supervisor' ? 'acacia' : '') }}">@if ($user->avatarUrl())<img src="{{ $user->avatarUrl() }}" alt="">@else{{ strtoupper(substr($user->name, 0, 2)) }}@endif</div>
                                    <div>
                                        <div class="cell-title">{{ $user->name }}</div>
                                        <div class="cell-sub">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="tag tag-terracotta">{{ ucfirst($user->role) }}</span></td>
                            <td>
                                <div class="cell-title">{{ $user->agent?->name ?? '—' }}</div>
                                <div class="cell-sub">{{ $user->agent?->code ?? '' }}</div>
                            </td>
                            <td>
                                <div class="cell-title">{{ $user->phone ?? '—' }}</div>
                                <div class="cell-sub">{{ $user->phone_verified_at ? 'Verified '.$user->phone_verified_at->format('d M Y') : 'Unverified' }}</div>
                            </td>
                            <td>
                                @if (($methodLabels[$user->id] ?? []) === [])
                                    <span class="cell-sub">Off</span>
                                @else
                                    @foreach (($methodLabels[$user->id] ?? []) as $label)
                                        <span class="tag {{ str_contains($label, 'default') ? 'tag-green' : 'tag-grey' }}" style="margin:1px 2px 1px 0;">{{ $label }}</span>
                                    @endforeach
                                @endif
                            </td>
                            <td><span class="tag {{ $user->is_active ? 'tag-green' : 'tag-grey' }}">{{ $user->is_active ? 'Active' : 'Disabled' }}</span></td>
                            <td class="cell-sub">{{ $user->last_login_at?->format('d M Y H:i') ?? 'Never' }}</td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ route('users.show', $user) }}" title="View" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </a>
                                    <a href="{{ route('users.edit', $user) }}" title="Edit" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"></path></svg>
                                    </a>
                                    <button class="danger" onclick="confirmDeleteUser('{{ $user->getRouteKey() }}', '{{ $user->name }}')" title="Delete">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-state"><h4>No users found</h4><p>Add your first team member.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- User details popup -->
    <div class="modal-backdrop" id="userDetailsModal">
        <style>
            #userDetailsModal .detail-list{display:flex;flex-direction:column;gap:8px;}
            #userDetailsModal .detail-row{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;padding:9px 0;border-bottom:1px solid var(--line);}
            #userDetailsModal .dk{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-soft);font-weight:700;flex:none;}
            #userDetailsModal .dv{font-size:13.5px;color:var(--coffee-900);font-weight:600;text-align:right;word-break:break-word;}
            #userDetailsModal .user-cols{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:18px;align-items:start;}
            #userDetailsModal .user-col{min-width:0;}
            #userDetailsModal .user-col-title{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-soft);font-weight:700;margin-bottom:8px;}
            @media (max-width:640px){
                #userDetailsModal .user-cols{grid-template-columns:1fr;gap:14px;}
            }
        </style>
        <div class="popup" style="max-width:620px; width:100%; margin:auto;">
            <div class="modal-head">
                <h3>User details</h3>
                <button class="modal-close" onclick="closeModal('userDetailsModal')">✕</button>
            </div>
            <div class="modal-body">
                <div class="user-cols">
                    <div class="user-col">
                        <div class="user-col-title">Account</div>
                        <div class="detail-list">
                            <div class="detail-row"><span class="dk">Name</span><span class="dv" id="userDetailName">—</span></div>
                            <div class="detail-row"><span class="dk">Email</span><span class="dv" id="userDetailEmail">—</span></div>
                            <div class="detail-row"><span class="dk">Role</span><span class="dv" id="userDetailRole">—</span></div>
                            <div class="detail-row"><span class="dk">Status</span><span class="dv" id="userDetailStatus">—</span></div>
                        </div>
                    </div>
                    <div class="user-col">
                        <div class="user-col-title">Assignment</div>
                        <div class="detail-list">
                            <div class="detail-row"><span class="dk">Cash point</span><span class="dv" id="userDetailAgent">—</span></div>
                            <div class="detail-row"><span class="dk">Phone</span><span class="dv" id="userDetailPhone">—</span></div>
                            <div class="detail-row"><span class="dk">Last login</span><span class="dv" id="userDetailLastLogin">—</span></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-primary" onclick="closeModal('userDetailsModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Add / edit user modal -->
    <div class="modal-backdrop" id="userModal">
        <div class="modal">
            <div class="modal-head">
                <h3 id="userModalTitle">Add user</h3>
                <button class="modal-close" onclick="closeModal('userModal')">✕</button>
            </div>
            <form id="userForm" data-user-form>
                <input type="hidden" name="_method" value="" id="userMethod">
                <input type="hidden" name="id" id="userId">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="field">
                            <label>Full name</label>
                            <input type="text" name="name" id="userName" placeholder="e.g. Baraka Mushi" required>
                        </div>
                        <div class="field">
                            <label>Email</label>
                            <input type="email" name="email" id="userEmail" placeholder="name@company.com" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field">
                            <label>Phone</label>
                            <input type="text" name="phone" id="userPhone" placeholder="07xxxxxxxx">
                        </div>
                        <div class="field">
                            <label>Password</label>
                            <input type="password" name="password" id="userPassword" placeholder="Min 6 characters" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field">
                            <label>Role</label>
                            <select name="role" id="userRole" onchange="toggleAgentField()">
                                <option value="cashier">Cashier</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Status</label>
                            <select name="is_active">
                                <option value="1">Active</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="field" id="agentFieldWrap">
                        <label>Linked cash point</label>
                        <select name="agent_id" id="userAgentId">
                            <option value="">— Not linked —</option>
                            @foreach ($agents as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->name }} ({{ $agent->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <p style="font-size:12px;color:var(--ink-soft);margin:0;" id="agentFieldHint">Leave password blank when editing to keep the current password.</p>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('userModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="userSubmitBtn">Save user</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirm delete modal -->
    <div class="modal-backdrop" id="confirmModalBackdrop">
        <div class="modal" style="max-width:400px;">
            <div class="modal-head">
                <h3>Delete user?</h3>
                <button class="modal-close" onclick="closeModal('confirmModalBackdrop')">✕</button>
            </div>
            <div class="modal-body">
                <p style="font-size:14px;color:var(--ink-soft);line-height:1.6;" id="confirmText">This user will lose access to the system.</p>
            </div>
            <div class="modal-foot">
                <button class="btn btn-ghost" onclick="closeModal('confirmModalBackdrop')">Cancel</button>
                <button class="btn btn-danger" onclick="executeDelete()">Delete</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        @php
            $jsonUsers = $users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'role' => $u->role,
                'agent_id' => $u->agent_id,
                'is_active' => (bool) $u->is_active,
            ])->values();
        @endphp
        const usersData = @json($jsonUsers);
        let pendingDeleteFunc = null;

        function setRoleFilter(role) {
            document.getElementById('fRole').value = role;
            document.getElementById('fRole').closest('form').submit();
        }

        function filterUserRows(q) {
            q = q.toLowerCase();
            document.querySelectorAll('#usersBody tr[data-id]').forEach(tr => {
                tr.style.display = (!q || tr.dataset.name.includes(q)) ? 'table-row' : 'none';
            });
        }

        function toggleAgentField() {
            const role = document.getElementById('userRole').value;
            document.getElementById('agentFieldWrap').style.display = role === 'cashier' ? '' : 'none';
        }

        function openUserModal(id = null) {
            const form = document.getElementById('userForm');
            if (id) {
                const u = usersData.find(x => Number(x.id) === Number(id));
                if (!u) return;
                document.getElementById('userModalTitle').textContent = 'Edit user';
                document.getElementById('userMethod').value = 'PUT';
                document.getElementById('userId').value = u.id;
                form.action = `/users/${u.id}`;
                document.getElementById('userName').value = u.name;
                document.getElementById('userEmail').value = u.email;
                document.getElementById('userPhone').value = u.phone || '';
                document.getElementById('userPassword').value = '';
                document.getElementById('userPassword').placeholder = 'Leave blank to keep current';
                document.getElementById('userRole').value = u.role;
                document.getElementById('userAgentId').value = u.agent_id || '';
                form.querySelector('[name="is_active"]').value = u.is_active ? '1' : '0';
                document.getElementById('userSubmitBtn').textContent = 'Update user';
                document.getElementById('agentFieldHint').style.display = '';
            } else {
                document.getElementById('userModalTitle').textContent = 'Add user';
                document.getElementById('userMethod').value = '';
                document.getElementById('userId').value = '';
                form.action = '{{ route("users.store") }}';
                document.getElementById('userName').value = '';
                document.getElementById('userEmail').value = '';
                document.getElementById('userPhone').value = '';
                document.getElementById('userPassword').value = '';
                document.getElementById('userPassword').placeholder = 'Min 6 characters';
                document.getElementById('userRole').value = 'cashier';
                document.getElementById('userAgentId').value = '';
                form.querySelector('[name="is_active"]').value = '1';
                document.getElementById('userSubmitBtn').textContent = 'Save user';
                document.getElementById('agentFieldHint').style.display = '';
            }
            toggleAgentField();
            openModal('userModal');
        }

        function editUser(id) { openUserModal(id); }

        function confirmDeleteUser(id, name) {
            document.getElementById('confirmText').textContent = 'Delete "' + name + '"? They will lose access to the system immediately.';
            pendingDeleteFunc = async () => {
                try {
                    const response = await fetch(`/users/${id}`, {
                        method: 'DELETE',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    });
                    const data = await response.json();
                    if (data.success) { toast(data.message, 'success'); setTimeout(() => location.reload(), 600); }
                    else { toast(data.message, 'error'); }
                } catch (err) { console.error(err); toast('Something went wrong!', 'error'); }
            };
            openModal('confirmModalBackdrop');
        }

        function executeDelete() {
            if (pendingDeleteFunc) pendingDeleteFunc();
            closeModal('confirmModalBackdrop');
        }

        document.querySelectorAll('#usersBody tr[data-id]').forEach(tr => {
            tr.style.cursor = 'pointer';
            tr.addEventListener('click', (e) => {
                if (e.target.closest('a, button, input, select, textarea, label')) return;
                openUserDetails(tr);
            });
        });

        function openUserDetails(tr) {
            const roleTag = tr.dataset.role === 'admin'
                ? '<span class="tag tag-gold">Admin</span>'
                : (tr.dataset.role === 'supervisor'
                    ? '<span class="tag tag-green">Supervisor</span>'
                    : '<span class="tag tag-terracotta">Cashier</span>');

            document.getElementById('userDetailName').textContent = tr.dataset.fullname || tr.dataset.name;
            document.getElementById('userDetailEmail').textContent = tr.dataset.email || '—';
            document.getElementById('userDetailRole').innerHTML = roleTag;
            document.getElementById('userDetailAgent').textContent = tr.dataset.agent
                ? tr.dataset.agent + ' (' + tr.dataset.agentcode + ')'
                : '—';
            document.getElementById('userDetailPhone').textContent = tr.dataset.phone || '—';
            document.getElementById('userDetailStatus').innerHTML = tr.dataset.active === '1'
                ? '<span class="tag tag-green">Active</span>'
                : '<span class="tag tag-grey">Disabled</span>';
            document.getElementById('userDetailLastLogin').textContent = tr.dataset.lastlogin || 'Never';

            openModal('userDetailsModal');
        }

        document.querySelectorAll('[data-user-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const method = document.getElementById('userMethod').value || 'POST';
                submitForm(form, { method, done: () => setTimeout(() => location.reload(), 600) });
            });
        });
    </script>
@endsection