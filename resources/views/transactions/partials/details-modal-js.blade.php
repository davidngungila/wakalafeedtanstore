{{-- Companion script for transactions.partials.details-modal.

     Defines openTransactionDetails(t) and the row binding helper. Values are
     written with textContent, never interpolated into innerHTML: customer
     names, notes and references originate from SMS bodies and must never be
     treated as markup. --}}
<script>
    window.txnModalFmt = function (n) {
        return 'TZS ' + Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 });
    };

    window.txnDetailRow = function (label, value, options) {
        options = options || {};
        const row = document.createElement('div');
        row.className = 'detail-row';

        const k = document.createElement('span');
        k.className = 'dk';
        k.textContent = label;

        const v = document.createElement('span');
        v.className = 'dv' + (options.wrap ? ' dv-wrap' : '') + (options.amount ? ' dv-amount' : '');
        v.textContent = value;

        row.appendChild(k);
        row.appendChild(v);

        return row;
    };

    window.txnModalMoney = function (value) {
        return value === null || value === undefined ? '—' : window.txnModalFmt(value);
    };

    window.openTransactionDetails = function (t, fallbackOperator) {
        if (!t) {
            if (typeof toast === 'function') toast('Transaction not found in local data. Please reload.', 'error');
            return;
        }

        const left = document.getElementById('txnModalLeft');
        const right = document.getElementById('txnModalRight');

        if (!left || !right) return;

        left.innerHTML = '';
        right.innerHTML = '';

        [
            ['Reference', t.reference || '—'],
            ['Provider ref', t.provider_reference || '—'],
            ['Date', t.created_at || '—'],
            ['Type', t.type_label || t.type || '—'],
            ['Network', t.network || '—'],
            ['Customer', t.customer_name || '—'],
            ['Phone', t.customer_phone || '—'],
            ['Status', String(t.status || 'unknown').toUpperCase()],
            ['Operator', t.operator || fallbackOperator || '—'],
        ].forEach(function (row) { left.appendChild(window.txnDetailRow(row[0], row[1])); });

        right.appendChild(window.txnDetailRow('Amount', window.txnModalMoney(t.amount), { amount: true }));

        [
            ['Fee', window.txnModalMoney(t.fee)],
            ['Commission', window.txnModalMoney(t.commission)],
            ['Running Float' + (t.network ? ' (' + t.network + ')' : ''), window.txnModalMoney(t.running_network_balance)],
            ['Total Float (all)', window.txnModalMoney(t.running_float_balance)],
            ['Running Cash', window.txnModalMoney(t.running_cash_balance)],
        ].forEach(function (row) { right.appendChild(window.txnDetailRow(row[0], row[1])); });

        if (t.reversal_reason) {
            right.appendChild(window.txnDetailRow('Reversal reason', t.reversal_reason, { wrap: true }));
        }
        if (t.notes) {
            right.appendChild(window.txnDetailRow('Notes', t.notes, { wrap: true }));
        }

        const receiptLink = document.getElementById('txnReceiptLink');
        if (receiptLink) {
            if (t.receipt_url) {
                receiptLink.href = t.receipt_url;
                receiptLink.style.display = '';
            } else {
                receiptLink.style.display = 'none';
            }
        }

        openModal('txnModal');
    };

    /**
     * Binds click-to-open on a transaction table body.
     *
     * @param {string} selector  e.g. '#cpTxnRows tr[data-id]'
     * @param {Array}  rows      the JSON payload already loaded by the page
     * @param {string} fallbackOperator
     */
    window.bindTransactionRows = function (selector, rows, fallbackOperator) {
        document.querySelectorAll(selector).forEach(function (tr) {
            tr.style.cursor = 'pointer';
            tr.addEventListener('click', function () {
                const t = rows.find(function (x) { return Number(x.id) === Number(tr.dataset.id); });
                window.openTransactionDetails(t, fallbackOperator);
            });
        });
    };
</script>