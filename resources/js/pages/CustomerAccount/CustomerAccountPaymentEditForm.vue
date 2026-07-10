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

const { normalizeDate, reverseDate } = useDateFormatter();

const props = defineProps({
    isProcessing: {
        type: Boolean,
        default: false,
    },
    account: {
        type: Object,
        default: null,
    },
    payment: {
        type: Object,
        required: true,
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
    amount: props.payment?.raw_amount ?? 0,
    payment_date: null,
    reference_no: props.payment?.invoice_no && props.payment.invoice_no !== '—' ? props.payment.invoice_no : '',
    payment_method: props.payment?.payment_method ?? 'Cash',
    check_date: null,
    check_number: props.payment?.check_number ?? '',
    notes: props.payment?.notes ?? '',
});

const isCheque = computed(() => form.payment_method === 'Cheque');

const isLoading = ref(true);
const isDialogOpen = ref(false);
const isBusy = computed(() => props.isProcessing);

// ─── Orders for this payment ──────────────────────────────────────────────────
const orders = ref([]);
const selectedOrderIds = ref([]);

const selectedTotal = computed(() =>
    orders.value
        .filter(o => selectedOrderIds.value.includes(o.id))
        .reduce((sum, o) => sum + Number(o.total), 0)
);

const fmt = (val) =>
    Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const toggleOrder = (id) => {
    const idx = selectedOrderIds.value.indexOf(id);
    if (idx === -1) selectedOrderIds.value.push(id);
    else selectedOrderIds.value.splice(idx, 1);
};

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
            sales_order_ids: selectedOrderIds.value,
        });
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
};

onMounted(async () => {
    if (props.payment?.raw_date) {
        form.payment_date = reverseDate(props.payment.raw_date.slice(0, 10));
    }
    if (props.payment?.check_date) {
        form.check_date = reverseDate(props.payment.check_date.slice(0, 10));
    }
    try {
        if (props.account?.csa_id && props.payment?.payment_id) {
            const res = await axios.get(
                `/customer-accounts/${props.account.csa_id}/orders-for-payment/${props.payment.payment_id}`,
                { headers: { Accept: 'application/json' } }
            );
            orders.value = res.data.orders ?? [];
            selectedOrderIds.value = orders.value.filter(o => o.selected).map(o => o.id);
        }
    } catch {
        toast.error('Failed to load invoices.');
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
                    <BaseField legend="Edit Payment" description="Update this payment entry">
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
                                                    class="text-xs uppercase tracking-wide text-muted-foreground font-medium">Payment</span>
                                                <p class="text-sm font-semibold font-mono truncate">{{
                                                    payment?.reference ?? '—' }}</p>
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
                                        <Input v-model="form.reference_no" placeholder="e.g. OR-001" />
                                    </Field>

                                    <template v-if="isCheque">
                                        <Field class="col-span-6">
                                            <FieldLabel class="font-normal">Cheque Number: <span
                                                    class="text-destructive">*</span></FieldLabel>
                                            <Input v-model="form.check_number" placeholder="e.g. 123456" />
                                        </Field>

                                        <Field class="col-span-6">
                                            <FieldLabel class="font-normal">Cheque Date: <span
                                                    class="text-destructive">*</span>
                                            </FieldLabel>
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

                <!-- Right: Invoice selection -->
                <div class="col-span-7">
                    <BaseField legend="Reference Invoices" description="Select sales orders covered by this payment">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-rows="1" :skeleton-cols="1">

                                <div v-if="orders.length === 0 && !isLoading"
                                    class="text-sm text-muted-foreground text-center py-6">
                                    No invoices available for this account.
                                </div>

                                <div v-else class="overflow-y-auto max-h-64 rounded-md border">
                                    <table class="w-full text-xs">
                                        <thead class="sticky top-0 bg-muted/80 z-10">
                                            <tr>
                                                <th class="w-8 px-3 py-2"></th>
                                                <th class="text-left px-3 py-2">Invoice No.</th>
                                                <th class="text-left px-3 py-2">Invoice Date</th>
                                                <th class="text-left px-3 py-2">Due Date</th>
                                                <th class="text-right px-3 py-2">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="order in orders" :key="order.id"
                                                class="border-t hover:bg-muted/40 cursor-pointer"
                                                :class="selectedOrderIds.includes(order.id) ? 'bg-primary/10' : ''"
                                                @click="toggleOrder(order.id)">
                                                <td class="px-3 py-2 text-center" @click.stop>
                                                    <Checkbox :model-value="selectedOrderIds.includes(order.id)"
                                                        @update:model-value="() => toggleOrder(order.id)" />
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

                                <div v-if="selectedOrderIds.length > 0"
                                    class="flex justify-between items-center text-xs text-muted-foreground pt-1 px-1">
                                    <span>{{ selectedOrderIds.length }} invoice(s) selected</span>
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
            <BaseButton type="button" :disabled="isBusy" @click="openConfirmDialog" transactionType="update"
                :loading="isProcessing" :skeleton="isLoading" />
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" transaction-type="update"
            @cancel="handleAlertClose" @confirm="handleSubmit" />
    </FormCard>
</template>
