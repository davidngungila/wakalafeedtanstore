{{-- Transaction details popup, shared by any page that lists transactions.
     Requires the companion script partial: transactions.partials.details-modal-js --}}
<div class="modal-backdrop" id="txnModal">
    <style>
        #txnModal .txn-cols{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.15fr);gap:18px;align-items:start;}
        #txnModal .txn-col{min-width:0;}
        #txnModal .txn-col-title{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-soft);font-weight:700;margin-bottom:8px;}
        #txnModal .detail-list{display:flex;flex-direction:column;gap:8px;}
        #txnModal .detail-row{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;padding:9px 0;border-bottom:1px solid var(--line);}
        #txnModal .detail-row:last-child{border-bottom:none;}
        #txnModal .dk{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-soft);font-weight:700;flex:none;}
        #txnModal .dv{font-size:13.5px;color:var(--coffee-900);font-weight:600;text-align:right;word-break:break-word;}
        #txnModal .dv-wrap{text-align:left;}
        #txnModal .dv-amount{font-size:16px;font-weight:700;}
        #txnModal .popup{max-height:90vh;overflow:hidden;display:flex;flex-direction:column;}
        #txnModal .modal-body{max-height:66vh;overflow-y:auto;}
        @media (max-width:640px){
            #txnModal .txn-cols{grid-template-columns:1fr;gap:14px;}
        }
    </style>
    <div class="popup" style="max-width:720px; width:100%; margin:auto;">
        <div class="modal-head">
            <h3>Transaction details</h3>
            <button class="modal-close" onclick="closeModal('txnModal')">✕</button>
        </div>
        <div class="modal-body">
            <div class="txn-cols">
                <div class="txn-col">
                    <div class="txn-col-title">Transaction</div>
                    <div class="detail-list" id="txnModalLeft"></div>
                </div>
                <div class="txn-col">
                    <div class="txn-col-title">Money &amp; balances</div>
                    <div class="detail-list" id="txnModalRight"></div>
                </div>
            </div>
        </div>
        <div class="modal-foot">
            <a class="btn btn-ghost" id="txnReceiptLink" href="#">View receipt</a>
            <button class="btn btn-primary" onclick="closeModal('txnModal')">Close</button>
        </div>
    </div>
</div>