<script setup>
import { CreditCard, CheckCircle, AlertCircle, FileText, Building2, X, Clock } from 'lucide-vue-next';

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['form-closed']);

const pd     = props.order?.payment_details;
const list   = props.order?.payment_list ?? [];
const status = props.order?.payment_status ?? (pd ? 'Paid' : 'Unpaid');

const totalRaw  = parseFloat((props.order?.total_amount ?? '0').replace(/,/g, ''));
const paidRaw   = list.reduce((s, p) => s + parseFloat(p.amount.replace(/,/g, '')), 0);
const remaining = Math.max(0, totalRaw - paidRaw);

const fmt = (n) => Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2 });

const methodColors = {
    'Cash':          'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    'Cheque':        'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    'Bank Transfer': 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
    'Online':        'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
};
const methodClass = (method) => methodColors[method] ?? 'bg-muted text-muted-foreground';
</script>

<template>
    <!-- Backdrop -->
    <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="emit('form-closed')" />

        <!-- Panel -->
        <div class="relative z-10 w-full max-w-lg rounded-2xl border bg-background shadow-2xl overflow-hidden">

            <!-- Header bar -->
            <div class="flex items-center justify-between border-b px-6 py-4 bg-muted/30">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                        <CreditCard class="h-4.5 w-4.5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold leading-tight">Payment Receipt</p>
                        <p class="text-xs text-muted-foreground">Invoice {{ order.invoice_no || '—' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span v-if="status === 'Paid'"
                        class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                        <CheckCircle class="h-3.5 w-3.5" /> Paid
                    </span>
                    <span v-else-if="status === 'Partial'"
                        class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">
                        <Clock class="h-3.5 w-3.5" /> Partial
                    </span>
                    <span v-else
                        class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        <AlertCircle class="h-3.5 w-3.5" /> Unpaid
                    </span>
                    <button @click="emit('form-closed')"
                        class="rounded-md p-1.5 text-muted-foreground hover:bg-muted transition-colors">
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="px-6 py-5 space-y-4">

                <!-- Invoice & Customer -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg border bg-muted/30 px-4 py-3 space-y-0.5">
                        <div
                            class="flex items-center gap-1.5 text-xs text-muted-foreground uppercase tracking-wide font-medium">
                            <FileText class="h-3.5 w-3.5" /> Invoice
                        </div>
                        <p class="text-sm font-semibold">{{ order.invoice_no || '—' }}</p>
                        <p class="text-xs text-muted-foreground">{{ order.invoice_date ?? '—' }}</p>
                    </div>
                    <div class="rounded-lg border bg-muted/30 px-4 py-3 space-y-0.5">
                        <div
                            class="flex items-center gap-1.5 text-xs text-muted-foreground uppercase tracking-wide font-medium">
                            <Building2 class="h-3.5 w-3.5" /> Customer
                        </div>
                        <p class="text-sm font-semibold truncate">{{ order.customer_name }}</p>
                        <p class="text-xs text-muted-foreground truncate">{{ order.account_name }}</p>
                    </div>
                </div>

                <!-- No payment -->
                <div v-if="list.length === 0"
                    class="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed py-10 text-muted-foreground">
                    <AlertCircle class="h-8 w-8 opacity-40" />
                    <p class="text-sm font-medium">No payment linked to this invoice</p>
                </div>

                <template v-else>
                    <!-- Summary cards -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg border bg-emerald-50 dark:bg-emerald-950/30 px-4 py-3">
                            <p class="text-xs text-muted-foreground uppercase tracking-wide font-medium mb-1">Total Paid</p>
                            <p class="text-xl font-bold text-emerald-700 dark:text-emerald-400 font-mono">{{ fmt(paidRaw) }}</p>
                        </div>
                        <div class="rounded-lg border px-4 py-3"
                            :class="status === 'Partial' ? 'bg-amber-50 dark:bg-amber-950/30' : 'bg-muted/30'">
                            <p class="text-xs text-muted-foreground uppercase tracking-wide font-medium mb-1">Remaining</p>
                            <p class="text-xl font-bold font-mono"
                                :class="status === 'Partial' ? 'text-amber-700 dark:text-amber-400' : 'text-green-600 dark:text-green-400'">
                                {{ fmt(remaining) }}
                            </p>
                        </div>
                    </div>

                    <!-- Payment list -->
                    <div class="rounded-lg border overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-muted/50">
                                <tr class="text-xs text-muted-foreground uppercase tracking-wide">
                                    <th class="text-left px-4 py-2 font-medium">Date</th>
                                    <th class="text-left px-4 py-2 font-medium">Method</th>
                                    <th class="text-left px-4 py-2 font-medium">Reference</th>
                                    <th class="text-right px-4 py-2 font-medium">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="(p, i) in list" :key="i">
                                    <td class="px-4 py-2.5 text-muted-foreground">{{ p.date ?? '—' }}</td>
                                    <td class="px-4 py-2.5">
                                        <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold', methodClass(p.method)]">
                                            {{ p.method ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 font-mono text-xs text-muted-foreground">{{ p.reference || '—' }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono font-semibold text-emerald-700 dark:text-emerald-400">{{ p.amount }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

            </div>

            <!-- Footer -->
            <div class="flex justify-end border-t px-6 py-4 bg-muted/20">
                <button @click="emit('form-closed')"
                    class="rounded-md px-4 py-2 text-sm font-medium bg-muted hover:bg-muted/80 transition-colors">
                    Close
                </button>
            </div>

        </div>
    </div>
</template>
