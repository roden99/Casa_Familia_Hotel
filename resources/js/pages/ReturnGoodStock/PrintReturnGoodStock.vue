<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Printer } from 'lucide-vue-next';
import { ref, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';

const props = defineProps({
    record: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isLoading = ref(true);
const rgsInfo = ref(null);
const items = ref([]);

const fmt = (v) =>
    Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

onMounted(async () => {
    try {
        const res = await axios.get(`/return-good-stocks/${props.record.id}`, {
            headers: { Accept: 'application/json' },
        });
        rgsInfo.value = res.data.rgs;
        items.value = res.data.items ?? [];
    } catch {
        toast.error('Failed to load RGS details.');
    } finally {
        isLoading.value = false;
    }
});

const print = () => {
    const rows = items.value.map(item => `
        <tr>
            <td>${item.product_name}</td>
            <td class="center">${item.lot_number ?? '—'}</td>
            <td class="center">${item.quantity}</td>
            <td class="right">${fmt(item.unit_price)}</td>
            <td class="right">${fmt(Number(item.quantity) * Number(item.unit_price))}</td>
        </tr>`).join('');

    const grandTotal = items.value.reduce((s, i) => s + Number(i.quantity) * Number(i.unit_price), 0);

    const win = window.open('', '_blank', 'width=850,height=1100');
    win.document.write(`<!DOCTYPE html>
<html>
<head>
<title>Return Good Stock — ${rgsInfo.value?.invoice_no ?? rgsInfo.value?.id}</title>
<style>
  @page { size: letter; margin: 0.75in; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 11px; color: #111; }
  .header { text-align: center; margin-bottom: 18px; }
  .header h1 { font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
  .header p { font-size: 11px; color: #555; margin-top: 2px; }
  .meta { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 11px; }
  .meta div { line-height: 1.8; }
  .meta .label { color: #555; }
  table { width: 100%; border-collapse: collapse; margin-top: 8px; }
  thead tr { background: #f0f0f0; }
  th { padding: 7px 8px; text-align: left; font-size: 11px; border-bottom: 1.5px solid #333; border-top: 1.5px solid #333; }
  td { padding: 6px 8px; font-size: 11px; border-bottom: 1px solid #ddd; vertical-align: top; }
  .center { text-align: center; }
  .right { text-align: right; }
  .total-row td { font-weight: bold; border-top: 1.5px solid #333; background: #f8f8f8; }
  .footer { margin-top: 40px; display: flex; justify-content: space-between; font-size: 11px; }
  .sig { text-align: center; width: 180px; }
  .sig .line { border-top: 1px solid #333; margin-bottom: 4px; margin-top: 50px; }
</style>
</head>
<body>
  <div class="header">
    <h1>Return Good Stock (RGS)</h1>
    <p>Stock Return Document</p>
  </div>
  <div class="meta">
    <div>
      <span class="label">RGS ID:</span> #${rgsInfo.value?.id}<br>
      <span class="label">RGS Date:</span> ${rgsInfo.value?.rgs_date ?? '—'}<br>
      <span class="label">Reference Invoice:</span> ${rgsInfo.value?.invoice_no ?? '—'}
    </div>
    <div style="text-align:right">
      <span class="label">Customer:</span> ${rgsInfo.value?.customer_name ?? '—'}<br>
      <span class="label">Account (P.M.R.):</span> ${rgsInfo.value?.account_name ?? '—'}<br>
      ${rgsInfo.value?.notes ? `<span class="label">Notes:</span> ${rgsInfo.value.notes}` : ''}
    </div>
  </div>
  <table>
    <thead>
      <tr>
        <th>Item</th>
        <th class="center" style="width:100px">Lot No.</th>
        <th class="center" style="width:60px">Qty</th>
        <th class="right" style="width:80px">Unit Price</th>
        <th class="right" style="width:90px">Amount</th>
      </tr>
    </thead>
    <tbody>
      ${rows}
      <tr class="total-row">
        <td colspan="4" class="right">Total</td>
        <td class="right">${fmt(grandTotal)}</td>
      </tr>
    </tbody>
  </table>
  <div class="footer">
    <div class="sig"><div class="line"></div>Prepared by</div>
    <div class="sig"><div class="line"></div>Received by</div>
    <div class="sig"><div class="line"></div>Approved by</div>
  </div>
</body>
</html>`);
    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); win.close(); }, 500);
};
</script>

<template>
    <FormCard :loading="isLoading" size="2xl" cardTitle="Print Return Good Stock">
        <div v-if="!isLoading" class="mt-4 space-y-3 text-sm">
            <div class="grid grid-cols-2 gap-3 border rounded-md p-3 bg-muted/30">
                <div><span class="text-muted-foreground">Customer:</span> <span class="font-medium">{{
                        rgsInfo?.customer_name }}</span></div>
                <div><span class="text-muted-foreground">Account:</span> <span class="font-medium">{{
                        rgsInfo?.account_name }}</span></div>
                <div><span class="text-muted-foreground">Reference Invoice:</span> <span class="font-medium">{{
                    rgsInfo?.invoice_no ?? '—' }}</span></div>
                <div><span class="text-muted-foreground">RGS Date:</span> <span class="font-medium">{{ rgsInfo?.rgs_date
                        }}</span></div>
                <div v-if="rgsInfo?.notes" class="col-span-2"><span class="text-muted-foreground">Notes:</span> <span
                        class="font-medium">{{ rgsInfo.notes }}</span></div>
            </div>
            <p class="text-xs text-muted-foreground">{{ items.length }} item(s) will be printed.</p>
        </div>
        <template #footer>
            <BaseButton type="button" @click="emit('form-closed')" transactionType="cancel" />
            <button v-if="!isLoading" type="button" @click="print"
                class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90">
                <Printer class="h-4 w-4" /> Print
            </button>
        </template>
    </FormCard>
</template>
