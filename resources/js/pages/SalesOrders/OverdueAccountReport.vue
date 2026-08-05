<script setup>
import { computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import { ref } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    account: { type: String, default: null },
    accounts: { type: Array, default: () => [] },
    month: { type: String, default: '' },
});

const selectedAccount = ref(props.account ?? '');

const accountOptions = computed(() => [
    { value: '', label: 'All Accounts' },
    ...props.accounts.map(a => ({ value: a, label: a })),
]);

const applyAccount = (val) => {
    selectedAccount.value = val;
    const url = new URL(window.location.href);
    if (val) url.searchParams.set('account', val);
    else url.searchParams.delete('account');
    router.get(url.pathname + url.search, {}, { preserveScroll: true });
};

const fmt = (n) => Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const col30to89 = (row) => row.days_overdue < 90 ? row.amount : 0;
const col90over = (row) => row.days_overdue >= 90 ? row.amount : 0;

// Group rows by customer, preserving sort order
const groupedCustomers = computed(() => {
    const map = new Map();
    for (const row of props.rows) {
        if (!map.has(row.customer_name)) {
            map.set(row.customer_name, { name: row.customer_name, address: row.address, charges: [] });
        }
        map.get(row.customer_name).charges.push(row);
    }
    return Array.from(map.values()).map(g => ({
        ...g,
        sub30to89: g.charges.reduce((s, r) => s + col30to89(r), 0),
        sub90over: g.charges.reduce((s, r) => s + col90over(r), 0),
    }));
});

const grand30to89 = computed(() => groupedCustomers.value.reduce((s, g) => s + g.sub30to89, 0));
const grand90over = computed(() => groupedCustomers.value.reduce((s, g) => s + g.sub90over, 0));
const grandTotal = computed(() => grand30to89.value + grand90over.value);

onMounted(() => {
    if (props.rows.length) window.print();
});

const printPage = () => window.print();
</script>

<template>

    <Head title="Overdue Account Report" />

    <!-- Screen controls (hidden on print) -->
    <div class="no-print flex items-center gap-3 p-4 bg-white border-b shadow-sm">
        <BaseCombobox :options="accountOptions" :modelValue="selectedAccount" @update:modelValue="applyAccount"
            placeholder="All Accounts" width="w-[220px]" />
        <button @click="printPage" class="px-4 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
            Print
        </button>
        <a href="/sales-orders" class="px-4 py-1.5 text-sm border rounded hover:bg-gray-50">← Back</a>
    </div>

    <!-- Print page -->
    <div class="report-page">

        <!-- Header -->
        <div class="text-center mb-1">
            <p class="text-base font-bold tracking-wide">OVERDUE ACCOUNT</p>
            <p class="text-xs">for the MONTH of {{ month }}</p>
        </div>

        <p class="text-xs font-semibold mb-1 underline">
            P.M.R.: <span class="ml-1">{{ account ?? 'ALL AREAS' }}</span>
        </p>

        <!-- Table -->
        <table class="report-table w-full text-xs border-collapse">
            <thead>
                <tr>
                    <th class="w-[35%]">CLIENT &amp; ADDRESS</th>
                    <th class="w-[12%]">DATE</th>
                    <th class="w-[18%]">S.I./D.R NO.</th>
                    <th class="w-[17%]">30-89 Days</th>
                    <th class="w-[18%]">90, 120 /OVER</th>
                </tr>
            </thead>
            <tbody>
                <template v-for="(group, gi) in groupedCustomers" :key="gi">
                    <!-- Customer name row -->
                    <tr class="customer-header">
                        <td colspan="5" class="font-bold py-0.5">
                            {{ group.name }}<span v-if="group.address"
                                class="ml-2 font-normal text-[10px] text-gray-500">{{ group.address }}</span>
                        </td>
                    </tr>
                    <!-- Charge rows -->
                    <tr v-for="(charge, ci) in group.charges" :key="ci">
                        <td></td>
                        <td class="text-center">{{ charge.invoice_date }}</td>
                        <td class="text-center">{{ charge.invoice_no }}</td>
                        <td class="text-right">{{ col30to89(charge) ? fmt(col30to89(charge)) : '' }}</td>
                        <td class="text-right">{{ col90over(charge) ? fmt(col90over(charge)) : '' }}</td>
                    </tr>
                    <!-- Customer subtotal -->
                    <tr class="subtotal-row">
                        <td></td>
                        <td></td>
                        <td class="text-right font-semibold pr-1">Total</td>
                        <td class="text-right font-semibold">{{ group.sub30to89 ? fmt(group.sub30to89) : '' }}</td>
                        <td class="text-right font-semibold">{{ group.sub90over ? fmt(group.sub90over) : '' }}</td>
                    </tr>
                    <!-- Spacer between customers -->
                    <tr class="spacer-row">
                        <td colspan="5"></td>
                    </tr>
                </template>
            </tbody>
            <tfoot>
                <tr class="grand-total-row font-bold">
                    <td></td>
                    <td></td>
                    <td class="text-right pr-1">GRAND TOTAL:</td>
                    <td class="text-right">{{ fmt(grand30to89) }}</td>
                    <td class="text-right">{{ fmt(grand90over) }}</td>
                </tr>
            </tfoot>
        </table>

    </div>
</template>

<style scoped>
/* ── Print page setup ───────────────────────────────────────────── */
@page {
    size: letter landscape;
    margin: 0.45in 0.5in;
}

.report-page {
    width: 100%;
    font-family: Arial, sans-serif;
    font-size: 11px;
    color: #000;
}

/* ── Table ─────────────────────────────────────────────────────── */
.report-table {
    border: 1.5px solid #000;
    border-collapse: collapse;
}

.report-table th,
.report-table td {
    border: 1px solid #555;
    padding: 2px 4px;
    vertical-align: top;
}

.report-table th {
    background: #fff;
    font-weight: bold;
    text-align: center;
    font-size: 10px;
    border: 1.5px solid #000;
}

.report-table tfoot td {
    border-top: 1.5px solid #000;
}

.customer-header td {
    background: #f3f4f6;
    border-top: 1.5px solid #000;
    padding: 3px 4px;
}

.subtotal-row td {
    border-top: 1px dashed #888;
    border-bottom: 1px solid #555;
}

.spacer-row td {
    height: 6px;
    border-left: 1px solid #555;
    border-right: 1px solid #555;
    border-top: none;
    border-bottom: none;
}

.grand-total-row td {
    border-top: 2px solid #000;
}

/* ── Print: hide controls ───────────────────────────────────────── */
@media print {
    .no-print {
        display: none !important;
    }

    .report-page {
        padding: 0;
        margin: 0;
    }
}

/* ── Screen: center page preview ───────────────────────────────── */
@media screen {
    body {
        background: #e5e7eb;
    }

    .report-page {
        background: #fff;
        max-width: 10.5in;
        margin: 1rem auto;
        padding: 0.5in;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .15);
        min-height: 8in;
    }
}
</style>
