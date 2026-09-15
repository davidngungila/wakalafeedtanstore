@php
    $routeName = request()->route()?->getName() ?? '';
    $item = fn (string $name, string $label) => [
        'name' => $name,
        'label' => $label,
        'active' => $routeName === $name,
        'href' => route($name),
    ];
    $nav = [
        $item('finance.index', 'Dashboard'),
        $item('finance.accounts.index', 'Chart of Accounts'),
        $item('finance.journals.index', 'Journal Entries'),
        $item('finance.ledger.index', 'General Ledger'),
        $item('finance.statements.income', 'Income Statement'),
        $item('finance.statements.balance', 'Balance Sheet'),
    ];
@endphp
<div class="table-toolbar" style="border:none;padding:10px 4px;">
    <div class="chip-filters">
        @foreach ($nav as $link)
            <a href="{{ $link['href'] }}" class="chip {{ $link['active'] ? 'active' : '' }}">{{ $link['label'] }}</a>
        @endforeach
    </div>
</div>