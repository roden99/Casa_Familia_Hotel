<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';

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
    notes: props.payment?.notes ?? '',
});

const isLoading = ref(true);
const isDialogOpen = ref(false);
const isBusy = computed(() => props.isProcessing);

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
        });
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
};

onMounted(() => {
    if (props.payment?.raw_date) {
        form.payment_date = reverseDate(props.payment.raw_date.slice(0, 10));
    }
    isLoading.value = false;
});
</script>

<template>
    <FormCard :loading="isProcessing" size="md">
        <form @submit.prevent class="space-y-4 mt-4">
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
                                        <p class="text-sm font-semibold truncate">{{ account?.display_name ?? '—' }}</p>
                                    </div>
                                    <div class="min-w-0 text-right">
                                        <span
                                            class="text-xs uppercase tracking-wide text-muted-foreground font-medium">Payment</span>
                                        <p class="text-sm font-semibold font-mono truncate">{{ payment?.reference ?? '—'
                                            }}</p>
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

                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Notes:</FieldLabel>
                                <Textarea v-model="form.notes" placeholder="Optional notes..." rows="2" />
                            </Field>

                        </div>
                    </FieldGroup>
                </template>
            </BaseField>
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
