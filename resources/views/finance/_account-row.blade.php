@php
    $account = \App\Models\Account::find($node['id']);
    $search = strtolower(trim(($node['code'] ?? '').' '.($node['name'] ?? '')));
@endphp
<tr data-search="{{ $search }}">
    <td class="cell-title">{{ $node['code'] }}</td>
    <td>
        <span class="cell-title">{{ $node['name'] }}</span>
        @if ($node['description'])
            <div class="cell-sub">{{ $node['description'] }}</div>
        @endif
    </td>
    <td><span class="tag {{ account_type_badge($node['type']) }}">{{ account_type_label($node['type']) }}</span></td>
    <td><span class="cell-sub">{{ $account->isDebitNormal() ? 'Debit' : 'Credit' }}</span></td>
    <td>
        @if ($node['is_active'])
            <span class="tag tag-green">Active</span>
        @else
            <span class="tag tag-grey">Inactive</span>
        @endif
    </td>
    <td>{{ $node['line_count'] ?? 0 }}</td>
    <td>
        @if (is_admin())
            <div class="row-actions">
                <button onclick="editAccount({{ $node['id'] }}, '{{ addslashes($node['code']) }}', '{{ addslashes($node['name']) }}', '{{ $node['type'] }}', {{ $node['parent_id'] ?? 'null' }}, '{{ addslashes($node['description'] ?? '') }}')" title="Edit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path><path d="m15 5 4 4"></path></svg>
                </button>
                @if (($node['line_count'] ?? 0) === 0 && ! count($node['children']))
                    <button class="danger" onclick="deleteAccount({{ $node['id'] }}, '{{ addslashes($node['code']) }}')" title="Delete">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                @endif
            </div>
        @endif
    </td>
</tr>
@foreach ($node['children'] ?? [] as $child)
    @include('finance._account-row', ['node' => $child])
@endforeach