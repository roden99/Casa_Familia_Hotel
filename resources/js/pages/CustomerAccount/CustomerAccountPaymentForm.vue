<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';
import axios from 'axios';

const { normalizeDate } = useDateFormatter();

const props = defineProps({
    isProcessing: {
        type: Boolean,
        default: false,
    },
    account: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const paymentMethodOptions = [
    { value: 'Cash', label: 'Cash' },
    { value: 'Cheque', label: 'Cheque' },
    { value: 'Bank Transfer', label: 'Bank Transfer' },
    { value: 'Online', label: 'Online' },
];

const form = useForm({
    amount: 0,
    payment_date: null,
    reference_no: '',
    payment_method: 'Cash',
    check_date: null,
    check_number: '',
    notes: '',
});

const isCheque = computed(() => form.payment_method === 'Cheque');

const isLoading = ref(true);
const isDialogOpen = ref(false);
const isBusy = computed(() => props.isProcessing);

// ─── Unpaid invoices (orders + manual) ───────────────────────────────────────────
const unpaidOrders = ref([]);
const selectedKeys = ref([]); // '{type}-{id}'

const key = (item) => `${item.type}-${item.id}`;

const selectedTotal = computed(() =>
    unpaidOrders.value
        .filter(o => selectedKeys.value.includes(key(o)))
        .reduce((sum, o) => sum + Number(o.total), 0)
);

const fmt = (val) =>
    Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const toggleOrder = (item) => {
    const k = key(item);
    const idx = selectedKeys.value.indexOf(k);
    if (idx === -1) selectedKeys.value.push(k);
    else selectedKeys.value.splice(idx, 1);
};

// ─── Handlers ─────────────────────────────────────────────────────────────────
const handleAlertClose = () => {
    isDialogOpen.value = false;
};

const isFormValidated = () => {
    if (!form.amount || Number(form.amount) <= 0) {
        toast.error('Please enter a valid payment amount.');
        return false;
    }
    if (!form.payment_date) {
        toast.error('Please select a payment date.');
        return false;
    }
    if (isCheque.value) {
        if (!form.check_number) {
            toast.error('Please enter a cheque number.');
            return false;
        }
        if (!form.check_date) {
            toast.error('Please enter a cheque date.');
            return false;
        }
    }
    return true;
};

const openConfirmDialog = () => {
    form.clearErrors();
    if (!isFormValidated()) return false;
    isDialogOpen.value = true;
    return true;
};

const handleSubmit = () => {
    try {
        emit('handleSubmit', {
            ...form.data(),
            payment_date: normalizeDate(form.payment_date),
            check_date: form.check_date ? normalizeDate(form.check_date) : null,
            sales_order_ids: selectedKeys.value.filter(k => k.startsWith('order-')).map(k => Number(k.split('-')[1])),
            invoice_ids: selectedKeys.value.filter(k => k.startsWith('invoice-')).map(k => Number(k.split('-')[1])),
        });
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
};

onMounted(async () => {
    try {
        if (props.account?.csa_id) {
            const res = await axios.get(`/customer-accounts/${props.account.csa_id}/unpaid-orders`, {
                headers: { Accept: 'application/json' },
            });
            unpaidOrders.value = res.data.orders ?? [];
        }
    } catch {
        toast.error('Failed to load unpaid invoices.');
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <FormCard :loading="isProcessing" size="lg">
        <form @submit.prevent class="space-y-4 mt-4">
            <div class="grid grid-cols-12 gap-6">

                <!-- Left: Payment details -->
                <div class="col-span-5">
                    <BaseField legend="Make Payment" description="Record a payment for this customer account">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-rows="1" :skeleton-cols="1">
                                <div class="grid w-full grid-cols-12 gap-4">

                                    <Field class="col-span-12">
                                        <div
                                            class="flex items-start justify-between gap-4 rounded-md border bg-muted/40 px-4 py-3">
                                            <div class="min-w-0">
                                                <span
                                                    class="text-xs uppercase tracking-wide text-muted-foreground font-medium">Customer</span>
                                                <p class="text-sm font-semibold truncate">{{ account?.display_name ??
                                                    '—' }}</p>
                                            </div>
                                            <div class="min-w-0 text-right">
                                                <span
                                                    class="text-xs uppercase tracking-wide text-muted-foreground font-medium">Account</span>
                                                <p class="text-sm font-semibold truncate">{{ account?.account_name ??
                                                    '—' }}</p>
                                            </div>
                                        </div>
                                    </Field>

                                    <Field class="col-span-6">
                                        <FieldLabel class="font-normal">Amount:</FieldLabel>
                                        <Input v-model.number="form.amount" type="number" min="0.01" step="0.01"
                                            placeholder="0.00" required />
                                    </Field>

                                    <Field class="col-span-6">
                                        <FieldLabel class="font-normal">Payment Date:</FieldLabel>
                                        <BaseDatePick v-model="form.payment_date" />
                                    </Field>

                                    <Field class="col-span-6">
                                        <FieldLabel class="font-normal">Payment Method:</FieldLabel>
                                        <BaseSelect v-model="form.payment_method" :options="paymentMethodOptions" />
                                    </Field>

                                    <Field class="col-span-6">
                                        <FieldLabel class="font-normal">Reference No.:</FieldLabel>
                                        <Input v-model="form.reference_no" placeholder="e.g. OR-001, CHQ-001" />
                                    </Field>

                                    <template v-if="isCheque">
                                        <Field class="col-span-6">
                                            <FieldLabel class="font-normal">Cheque Number: <span
                                                    class="text-destructive">*</span></FieldLabel>
                                            <Input v-model="form.check_number" placeholder="e.g. 123456" />
                                        </Field>

                                        <Field class="col-span-6">
                                            <FieldLabel class="font-normal">Cheque Date: <span
                                                    class="text-destructive">*</span></FieldLabel>
                                            <BaseDatePick v-model="form.check_date" />
                                        </Field>
                                    </template>

                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Notes:</FieldLabel>
                                        <Textarea v-model="form.notes" placeholder="Optional notes..." rows="2" />
                                    </Field>

                                </div>
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

                <!-- Right: Unpaid invoices -->
                <div class="col-span-7">
                    <BaseField legend="Reference Invoices" description="Select sales orders covered by this payment">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-rows="1" :skeleton-cols="1">

                                <div v-if="unpaidOrders.length === 0 && !isLoading"
                                    class="text-sm text-muted-foreground text-center py-6">
                                    No unpaid invoices for this account.
                                </div>

                                <div v-else class="overflow-y-auto max-h-64 rounded-md border">
                                    <table class="w-full text-xs">
                                        <thead class="sticky top-0 bg-muted/80 z-10">
                                            <tr>
                                                <th class="w-8 px-3 py-2"></th>
                                                <th class="text-left px-3 py-2">Type</th>
                                                <th class="text-left px-3 py-2">Invoice No.</th>
                                                <th class="text-left px-3 py-2">Date</th>
                                                <th class="text-left px-3 py-2">Due</th>
                                                <th class="text-right px-3 py-2">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="order in unpaidOrders" :key="key(order)"
                                                class="border-t hover:bg-muted/40 cursor-pointer"
                                                :class="selectedKeys.includes(key(order)) ? 'bg-primary/10' : ''"
                                                @click="toggleOrder(order)">
                                                <td class="px-3 py-2 text-center" @click.stop>
                                                    <Checkbox :model-value="selectedKeys.includes(key(order))"
                                                        @update:model-value="() => toggleOrder(order)" />
                                                </td>
                                                <td class="px-3 py-2">
                                                    <span
                                                        :class="order.type === 'order'
                                                            ? 'inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
                                                            : 'inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'">
                                                        {{ order.type === 'order' ? 'SO' : 'INV' }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2 font-medium">{{ order.invoice_no }}</td>
                                                <td class="px-3 py-2 text-muted-foreground">{{ order.invoice_date ?? '—'
                                                }}</td>
                                                <td class="px-3 py-2"
                                                    :class="order.due_date && new Date(order.due_date) < new Date() ? 'text-red-600 font-semibold' : 'text-muted-foreground'">
                                                    {{ order.due_date ?? '—' }}
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono">{{ fmt(order.total) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div v-if="selectedKeys.length > 0"
                                    class="flex justify-between items-center text-xs text-muted-foreground pt-1 px-1">
                                    <span>{{ selectedKeys.length }} invoice(s) selected</span>
                                    <span class="font-semibold text-foreground">Total: {{ fmt(selectedTotal) }}</span>
                                </div>

                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

            </div>
        </form>

        <template #footer>
            <BaseButton type="button" :disabled="isBusy" @click="emit('form-closed')" transactionType="cancel"
                :skeleton="isLoading" />
            <BaseButton type="button" :disabled="isBusy" @click="openConfirmDialog" transactionType="create"
                :loading="isProcessing" :skeleton="isLoading" />
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" transaction-type="create"
            @cancel="handleAlertClose" @confirm="handleSubmit" />
    </FormCard>
</template>
