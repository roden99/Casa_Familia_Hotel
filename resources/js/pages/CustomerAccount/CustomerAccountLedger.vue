<script setup>
import { ref, onMounted, computed } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import CustomerAccountInvoiceEdit from './CustomerAccountInvoiceEdit.vue';
import CustomerAccountPaymentEdit from './CustomerAccountPaymentEdit.vue';

const props = defineProps({
    account: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['form-closed']);

const isLoading = ref(true);
const accountInfo = ref(null);
const ledger = ref([]);

const reversedLedger = computed(() => [...ledger.value].reverse());

onMounted(async () => {
    try {
        const res = await axios.get(`/customer-accounts/${props.account.csa_id}/ledger`, {
            headers: { Accept: 'application/json' },
        });
        accountInfo.value = res.data.account;
        ledger.value = res.data.ledger;
    } catch (error) {
        console.error('Failed to load ledger:', error);
        toast.error('Failed to load account ledger.');
    } finally {
        isLoading.value = false;
    }
});

const badgeVariant = (type) => {
    if (type === 'INVOICE') return 'destructive';
    if (type === 'PAYMENT') return 'default';
    if (type === 'FORWARD') return 'secondary';
    return 'outline';
};

const balanceClass = computed(() => {
    if (!accountInfo.value) return '';
    const bal = parseFloat(accountInfo.value.balance.replace(/,/g, ''));
    if (bal > 0) return 'text-destructive';
    if (bal < 0) return 'text-green-600 dark:text-green-400';
    return 'text-foreground';
});

const showEditInvoiceModal = ref(false);
const selectedInvoice = ref(null);
const showEditPaymentModal = ref(false);
const selectedPayment = ref(null);

const openEditInvoice = (entry) => {
    selectedInvoice.value = entry;
    showEditInvoiceModal.value = true;
};

const openEditPayment = (entry) => {
    selectedPayment.value = entry;
    showEditPaymentModal.value = true;
};

const reloadLedger = async () => {
    isLoading.value = true;
    try {
        const res = await axios.get(`/customer-accounts/${props.account.csa_id}/ledger`, {
            headers: { Accept: 'application/json' },
        });
        accountInfo.value = res.data.account;
        ledger.value = res.data.ledger;
    } catch {
        toast.error('Failed to reload ledger.');
    } finally {
        isLoading.value = false;
    }
};

const handleInvoiceEditClosed = async () => {
    showEditInvoiceModal.value = false;
    await reloadLedger();
};

const handlePaymentEditClosed = async () => {
    showEditPaymentModal.value = false;
    await reloadLedger();
};

// ── Delete ────────────────────────────────────────────────────────────────────
const showDeleteDialog = ref(false);
const isDeleting = ref(false);
const deleteTarget = ref(null); // { type: 'invoice'|'payment', id, reference }

const openDelete = (entry) => {
    deleteTarget.value = entry;
    showDeleteDialog.value = true;
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    const { type, invoice_id, payment_id, reference } = deleteTarget.value;
    const url = type === 'INVOICE'
        ? `/customer-accounts/${props.account.csa_id}/invoices/${invoice_id}`
        : `/customer-accounts/${props.account.csa_id}/payments/${payment_id}`;

    isDeleting.value = true;
    router.delete(url, {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: async () => {
            toast.success('Deleted', { description: `${reference} removed.` });
            showDeleteDialog.value = false;
            deleteTarget.value = null;
            await reloadLedger();
        },
        onError: () => {
            toast.error('Failed to delete entry.');
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};
</script>

<template>
    <FormCard size="4xl" :loading="isLoading">
        <div class="space-y-5 mt-4">

            <!-- Account info header -->
            <div v-if="accountInfo" class="rounded-lg border bg-muted/40 px-5 py-4">
                <p class="text-base font-semibold mb-3">{{ accountInfo.customer }}</p>
                <div class="flex flex-wrap gap-6 text-sm text-muted-foreground">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs uppercase tracking-wide font-medium">Account</span>
                        <span class="text-foreground font-semibold">{{ accountInfo.account_name }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs uppercase tracking-wide font-medium">Outstanding Balance</span>
                        <span :class="['font-bold text-lg', balanceClass]">{{ accountInfo.balance }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs uppercase tracking-wide font-medium">Transactions</span>
                        <span class="text-foreground font-semibold">{{ ledger.length }}</span>
                    </div>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="isLoading" class="flex items-center justify-center py-16 text-muted-foreground text-sm">
                Loading ledger...
            </div>

            <!-- Empty -->
            <div v-else-if="ledger.length === 0"
                class="flex items-center justify-center py-16 text-muted-foreground text-sm">
                No transactions found for this account.
            </div>

            <!-- Ledger table -->
            <div v-else class="rounded-md border overflow-hidden">
                <div class="overflow-y-auto max-h-[55vh]">
                    <Table>
                        <TableHeader class="sticky top-0 z-10 bg-background">
                            <TableRow class="bg-muted/60 hover:bg-muted/60">
                                <TableHead class="w-28 font-semibold">Type</TableHead>
                                <TableHead class="font-semibold">Reference</TableHead>
                                <TableHead class="w-36 font-semibold">Invoice #</TableHead>
                                <TableHead class="font-semibold">Notes</TableHead>
                                <TableHead class="text-right w-32 font-semibold text-destructive">Debit</TableHead>
                                <TableHead class="text-right w-32 font-semibold text-green-700 dark:text-green-400">
                                    Credit
                                </TableHead>
                                <TableHead class="text-right w-32 font-semibold">Balance</TableHead>
                                <TableHead class="text-right w-28 font-semibold">Date</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(entry, index) in reversedLedger" :key="index"
                                :class="entry.type === 'FORWARD' ? 'bg-muted/30' : ''">
                                <TableCell>
                                    <Badge :variant="badgeVariant(entry.type)" class="text-xs">
                                        {{ entry.type }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-sm">
                                    <div class="flex items-center gap-2">
                                        <span>{{ entry.reference }}</span>
                                        <Button v-if="entry.is_manual" variant="ghost" size="icon"
                                            class="h-6 w-6 text-muted-foreground hover:text-primary"
                                            @click="openEditInvoice(entry)">
                                            <Pencil class="h-3 w-3" />
                                        </Button>
                                        <Button v-if="entry.is_manual" variant="ghost" size="icon"
                                            class="h-6 w-6 text-muted-foreground hover:text-destructive"
                                            @click="openDelete(entry)">
                                            <Trash2 class="h-3 w-3" />
                                        </Button>
                                        <Button v-if="entry.is_payment" variant="ghost" size="icon"
                                            class="h-6 w-6 text-muted-foreground hover:text-primary"
                                            @click="openEditPayment(entry)">
                                            <Pencil class="h-3 w-3" />
                                        </Button>
                                        <Button v-if="entry.is_payment" variant="ghost" size="icon"
                                            class="h-6 w-6 text-muted-foreground hover:text-destructive"
                                            @click="openDelete(entry)">
                                            <Trash2 class="h-3 w-3" />
                                        </Button>
                                    </div>
                                </TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ entry.invoice_no }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground italic">
                                    {{ entry.notes || '' }}
                                </TableCell>
                                <TableCell class="text-right font-mono text-sm font-medium text-destructive">
                                    {{ entry.type === 'INVOICE' ? entry.amount : '' }}
                                </TableCell>
                                <TableCell
                                    class="text-right font-mono text-sm font-medium text-green-600 dark:text-green-400">
                                    {{ entry.type === 'PAYMENT' ? entry.amount : '' }}
                                </TableCell>
                                <TableCell class="text-right font-mono text-sm font-bold">
                                    {{ entry.balance }}
                                </TableCell>
                                <TableCell class="text-right text-sm text-muted-foreground whitespace-nowrap">
                                    {{ entry.date }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

        </div>

        <template #footer>
            <button
                class="ml-auto inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm hover:bg-accent hover:text-accent-foreground"
                @click="emit('form-closed')">
                Close
            </button>
        </template>
    </FormCard>

    <CustomerAccountInvoiceEdit v-if="showEditInvoiceModal" :account="account" :invoice="selectedInvoice"
        @form-closed="handleInvoiceEditClosed" />

    <CustomerAccountPaymentEdit v-if="showEditPaymentModal" :account="account" :payment="selectedPayment"
        @form-closed="handlePaymentEditClosed" />

    <BaseAlertDialog v-model:open="showDeleteDialog" :loading="isDeleting" transaction-type="delete"
        @cancel="showDeleteDialog = false" @confirm="confirmDelete" />
</template>
