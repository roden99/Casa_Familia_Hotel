<script setup>
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Printer, X } from 'lucide-vue-next';

const props = defineProps({
    receipt: { type: Object, required: true },
});

const emit = defineEmits(['close']);

const fmt = (val) =>
    Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const lineTotal = (item) => {
    const disc = Number(item.discount_percentage) || 0;
    return Number(item.quantity) * Number(item.unit_price) * (1 - disc / 100);
};

const grandTotal = computed(() =>
    props.receipt.items.reduce((sum, item) => sum + lineTotal(item), 0)
);

const printReceipt = () => {
    const win = window.open('', '_blank', 'width=420,height=680');
    win.document.write(`<!DOCTYPE html><html><head><title>Receipt</title><style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 12px; width: 80mm; padding: 8px; }
        h2 { text-align: center; font-size: 15px; margin-bottom: 2px; }
        .center { text-align: center; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; margin: 2px 0; }
        .item-name { flex: 1; word-break: break-word; padding-right: 6px; }
        .amount { text-align: right; white-space: nowrap; }
        .total { font-weight: bold; font-size: 13px; }
        .footer { text-align: center; margin-top: 10px; font-size: 11px; }
    </style></head><body>
        <h2>xWorks POS</h2>
        <p class="center" style="font-size:11px; margin-bottom:4px;">Official Receipt</p>
        <div class="divider"></div>
        <div class="row"><span>Date:</span><span>${props.receipt.sale_date ?? '—'}</span></div>
        <div class="row"><span>Receipt No.:</span><span>${props.receipt.receipt_no || '—'}</span></div>
        <div class="row"><span>Payment:</span><span>${(props.receipt.payment_method ?? '').toUpperCase()}</span></div>
        <div class="row"><span>Customer:</span><span>${props.receipt.customer_name || 'Walk-in'}</span></div>
        ${props.receipt.notes ? `<div class="row"><span>Notes:</span><span>${props.receipt.notes}</span></div>` : ''}
        <div class="divider"></div>
        ${props.receipt.items.map(item => {
        const disc = Number(item.discount_percentage) || 0;
        const total = Number(item.quantity) * Number(item.unit_price) * (1 - disc / 100);
        const lotLine = (item.lot_number || item.expiration_date)
            ? `<div style="padding-left:8px; font-size:10px; color:#555;">Lot: ${item.lot_number ?? '—'} | Exp: ${item.expiration_date ?? '—'}</div>`
            : '';
        return `
            <div style="margin-bottom:4px;">
                <div class="item-name">${item.product_name ?? '—'}</div>
                ${lotLine}
                <div class="row" style="padding-left:8px;">
                    <span>${item.quantity} x ${fmt(item.unit_price)}${disc ? ` (-${disc}%)` : ''}</span>
                    <span class="amount">${fmt(total)}</span>
                </div>
            </div>`;
    }).join('')}
        <div class="divider"></div>
        <div class="row total"><span>TOTAL</span><span>${fmt(grandTotal.value)}</span></div>
        ${props.receipt.tendered != null ? `
        <div class="row"><span>Tendered</span><span>${fmt(props.receipt.tendered)}</span></div>
        <div class="row total"><span>Change</span><span>${fmt(props.receipt.change)}</span></div>` : ''}
        <div class="divider"></div>
        <p class="footer">Thank you for your purchase!</p>
        <div class="divider"></div>
        <p class="footer" style="font-size:10px; font-style:italic;">This is not a valid receipt and cannot be used as an input tax credit.</p>
    </body></html>`);
    win.document.close();
    win.focus();
    win.print();
};
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-background rounded-xl shadow-2xl w-full max-w-sm p-6 flex flex-col gap-4">

                <!-- Receipt preview -->
                <div class="font-mono text-xs border rounded-lg p-4 bg-muted/30 space-y-1 max-h-[70vh] overflow-y-auto">
                    <p class="text-center font-bold text-sm">Pharmahealth Enterprises</p>
                    <p class="text-center text-muted-foreground">***</p>
                    <hr class="border-dashed my-2" />
                    <div class="flex justify-between"><span class="text-muted-foreground">Date:</span><span>{{
                        receipt.sale_date ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-muted-foreground">Receipt No.:</span><span>{{
                        receipt.receipt_no || '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-muted-foreground">Payment:</span><span>{{
                        (receipt.payment_method ?? '').toUpperCase() }}</span></div>
                    <div class="flex justify-between"><span class="text-muted-foreground">Customer:</span><span>{{
                        receipt.customer_name || 'Walk-in' }}</span></div>
                    <div v-if="receipt.notes" class="flex justify-between"><span
                            class="text-muted-foreground">Notes:</span><span>{{ receipt.notes }}</span></div>
                    <hr class="border-dashed my-2" />
                    <div v-for="(item, i) in receipt.items" :key="i" class="mb-1">
                        <p class="font-medium break-words">{{ item.product_name ?? '—' }}</p>
                        <p v-if="item.lot_number || item.expiration_date" class="pl-2 text-muted-foreground" style="font-size:10px;">
                            Lot: {{ item.lot_number ?? '—' }} | Exp: {{ item.expiration_date ?? '—' }}
                        </p>
                        <div class="flex justify-between pl-2 text-muted-foreground">
                            <span>{{ item.quantity }} × {{ fmt(item.unit_price) }}{{ item.discount_percentage ? ` (-${item.discount_percentage}%)` : '' }}</span>
                            <span class="font-medium text-foreground">{{ fmt(lineTotal(item)) }}</span>
                        </div>
                    </div>
                    <hr class="border-dashed my-2" />
                    <div class="flex justify-between font-bold text-sm">
                        <span>TOTAL</span>
                        <span>{{ fmt(grandTotal) }}</span>
                    </div>
                    <template v-if="receipt.tendered != null">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Tendered</span>
                            <span>{{ fmt(receipt.tendered) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-sm text-emerald-600">
                            <span>Change</span>
                            <span>{{ fmt(receipt.change) }}</span>
                        </div>
                    </template>
                    <hr class="border-dashed my-2" />
                    <p class="text-center text-muted-foreground">Thank you for your purchase!</p>
                    <p class="text-center text-muted-foreground italic text-[10px] mt-1">This is not a valid receipt and
                        cannot be used as
                        an input tax credit.</p>
                </div>

                <!-- Actions -->
                <div class="flex gap-2 justify-end">
                    <Button variant="outline" @click="emit('close')">
                        <X class="h-4 w-4 mr-1" /> Close
                    </Button>
                    <Button @click="printReceipt">
                        <Printer class="h-4 w-4 mr-1" /> Print Receipt
                    </Button>
                </div>

            </div>
        </div>
    </Teleport>
</template>
