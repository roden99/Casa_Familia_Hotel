<script setup>
import { ref, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';
import FormCard from '@/components/FormCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Printer } from 'lucide-vue-next';

const props = defineProps({
    transfer: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isLoading = ref(true);
const items = ref([]);
const transferInfo = ref(null);

onMounted(async () => {
    try {
        const res = await axios.get(`/transfer-stocks/${props.transfer.id}`, {
            headers: { Accept: 'application/json' },
        });
        transferInfo.value = res.data.transfer;
        items.value = res.data.items;
    } catch {
        toast.error('Failed to load transfer details.');
    } finally {
        isLoading.value = false;
    }
});

const print = () => {
    const win = window.open('', '_blank', 'width=850,height=1100');
    const rows = items.value.map(item => `
        <tr>
            <td>${item.product_name}</td>
            <td class="center">${item.lot_number ?? '—'}</td>
            <td class="center">${item.quantity}</td>
            <td class="center">${item.multiplier}</td>
            <td class="center">${item.pos_qty_added}</td>
        </tr>`).join('');

    win.document.write(`<!DOCTYPE html>
<html>
<head>
<title>Transfer Stock Report</title>
<style>
  @page { size: letter; margin: 0.75in; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 11px; color: #111; }
  .header { text-align: center; margin-bottom: 18px; }
  .header h1 { font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
  .header p { font-size: 11px; color: #555; margin-top: 2px; }
  .meta { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 11px; }
  .meta div { line-height: 1.7; }
  .meta .label { color: #555; }
  table { width: 100%; border-collapse: collapse; margin-top: 8px; }
  thead tr { background: #f0f0f0; }
  th { padding: 7px 8px; text-align: left; font-size: 11px; border-bottom: 1.5px solid #333; border-top: 1.5px solid #333; }
  td { padding: 6px 8px; font-size: 11px; border-bottom: 1px solid #ddd; vertical-align: top; }
  .center { text-align: center; }
  .footer { margin-top: 32px; display: flex; justify-content: space-between; font-size: 11px; }
  .sig { text-align: center; width: 180px; }
  .sig .line { border-top: 1px solid #333; margin-bottom: 4px; margin-top: 40px; }
  .total-row td { font-weight: bold; border-top: 1.5px solid #333; }
</style>
</head>
<body>
  <div class="header">
    <h1>Transfer Stock Report</h1>
    <p>Warehouse → POS Transfer</p>
  </div>

  <div class="meta">
    <div>
      <span class="label">Transfer ID:</span> #${transferInfo.value?.id}<br>
      <span class="label">Transfer Date:</span> ${transferInfo.value?.transfer_date ?? '—'}
    </div>
    <div style="text-align:right">
      <span class="label">Total Items:</span> ${items.value.length}<br>
      <span class="label">Notes:</span> ${transferInfo.value?.notes || '—'}
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Product</th>
        <th class="center">Lot No.</th>
        <th class="center">Qty</th>
        <th class="center">Multiplier</th>
        <th class="center">POS Qty Added</th>
      </tr>
    </thead>
    <tbody>
      ${rows}
    </tbody>
    <tfoot>
      <tr class="total-row">
        <td colspan="4" style="text-align:right">Total POS Qty Added:</td>
        <td class="center">${items.value.reduce((s, i) => s + Number(i.pos_qty_added), 0)}</td>
      </tr>
    </tfoot>
  </table>

  <div class="footer">
    <div class="sig">
      <div class="line"></div>
      Prepared By
    </div>
    <div class="sig">
      <div class="line"></div>
      Checked By
    </div>
    <div class="sig">
      <div class="line"></div>
      Received By
    </div>
  </div>
</body>
</html>`);
    win.document.close();
    win.focus();
    win.print();
};
</script>

<template>
    <FormCard :loading="isLoading" size="3xl" cardTitle="Print Transfer Stock Report">
        <div v-if="!isLoading" class="space-y-4 mt-4">
            <div class="rounded-lg border bg-muted/40 px-5 py-4 text-sm">
                <div class="flex flex-wrap gap-6">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs uppercase tracking-wide font-medium text-muted-foreground">Transfer ID</span>
                        <span class="font-semibold">#{{ transferInfo?.id }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs uppercase tracking-wide font-medium text-muted-foreground">Date</span>
                        <span class="font-semibold">{{ transferInfo?.transfer_date }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs uppercase tracking-wide font-medium text-muted-foreground">Items</span>
                        <span class="font-semibold">{{ items.length }}</span>
                    </div>
                    <div v-if="transferInfo?.notes" class="flex flex-col gap-0.5">
                        <span class="text-xs uppercase tracking-wide font-medium text-muted-foreground">Notes</span>
                        <span class="font-semibold">{{ transferInfo.notes }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-md border overflow-hidden">
                <div class="overflow-y-auto max-h-[40vh]">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 z-10 bg-muted/60">
                            <tr class="border-b">
                                <th class="px-3 py-2 text-left font-semibold">Product</th>
                                <th class="px-3 py-2 text-center font-semibold w-28">Lot No.</th>
                                <th class="px-3 py-2 text-center font-semibold w-20">Qty</th>
                                <th class="px-3 py-2 text-center font-semibold w-24">Multiplier</th>
                                <th class="px-3 py-2 text-center font-semibold w-28">POS Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="items.length === 0">
                                <td colspan="5" class="text-center text-muted-foreground py-4">No items.</td>
                            </tr>
                            <tr v-for="item in items" :key="item.id" class="border-b last:border-0">
                                <td class="px-3 py-2">{{ item.product_name }}</td>
                                <td class="px-3 py-2 text-center font-mono text-xs">{{ item.lot_number ?? '—' }}</td>
                                <td class="px-3 py-2 text-center">{{ item.quantity }}</td>
                                <td class="px-3 py-2 text-center">{{ item.multiplier }}</td>
                                <td class="px-3 py-2 text-center font-medium text-teal-600">{{ item.pos_qty_added }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <template #footer>
            <button
                class="ml-auto inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm hover:bg-accent hover:text-accent-foreground"
                @click="emit('form-closed')">
                Close
            </button>
            <button
                class="inline-flex items-center gap-2 justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 disabled:opacity-50"
                :disabled="isLoading"
                @click="print">
                <Printer class="h-4 w-4" /> Print
            </button>
        </template>
    </FormCard>
</template>
