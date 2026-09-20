@php
    $pickerId = 'netPicker' . ($pickerKey ?? '');
    $pickerSelected = $selectedIds ?? [];
@endphp
<div class="field">
    <label>Networks (access)</label>
    <div class="net-picker" data-net-picker id="{{ $pickerId }}">
        <button type="button" class="net-picker-btn" aria-haspopup="listbox" aria-expanded="false">
            <span data-net-picker-label class="net-picker-label">Choose networks</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </button>
        <div class="net-picker-menu" role="listbox" hidden>
            @foreach ($networks as $network)
                <label class="net-option" role="option">
                    <input type="checkbox" name="network_ids[]" value="{{ $network->id }}" {{ in_array($network->id, $pickerSelected, true) ? 'checked' : '' }}>
                    <span class="net-dot" style="background:{{ $network->color }};"></span>
                    {{ $network->name }}
                </label>
            @endforeach
        </div>
    </div>
</div>
<script>
    (function () {
        function setupPicker(root) {
            const btn = root.querySelector('.net-picker-btn');
            const menu = root.querySelector('.net-picker-menu');
            const label = root.querySelector('[data-net-picker-label]');
            const boxes = Array.from(root.querySelectorAll('input[type="checkbox"]'));

            const noneText = 'Choose networks';
            const color = (id) => root.querySelector('input[value="' + id + '"]')?.closest('.net-option')?.querySelector('.net-dot')?.style?.background || '#999';

            function refresh() {
                const checked = boxes.filter(b => b.checked);
                if (!checked.length) {
                    label.textContent = noneText;
                } else if (checked.length <= 3) {
                    label.innerHTML = checked.map(b => '<span class="net-picker-tag"><span class="net-dot" style="background:' + color(b.value) + '"></span>' + b.closest('.net-option').textContent.trim() + '</span>').join(' ');
                } else {
                    label.textContent = checked.length + ' networks selected';
                }
            }

            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const open = menu.hidden;
                document.querySelectorAll('.net-picker-menu:not([hidden])').forEach(m => { m.hidden = true; m.closest('[data-net-picker]').querySelector('.net-picker-btn').setAttribute('aria-expanded', 'false'); });
                menu.hidden = !open;
                btn.setAttribute('aria-expanded', String(open));
            });

            boxes.forEach(b => b.addEventListener('change', refresh));
            refresh();
        }

        document.querySelectorAll('[data-net-picker]').forEach(setupPicker);

        document.addEventListener('click', () => {
            document.querySelectorAll('.net-picker-menu:not([hidden])').forEach(menu => {
                menu.hidden = true;
                menu.closest('[data-net-picker]').querySelector('.net-picker-btn').setAttribute('aria-expanded', 'false');
            });
        });
    })();
</script>