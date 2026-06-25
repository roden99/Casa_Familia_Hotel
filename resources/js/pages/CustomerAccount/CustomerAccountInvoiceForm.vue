<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';

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

const form = useForm({
    reference_no: '',
    invoice_date: null,
    amount: 0,
    notes: '',
});

const isLoading = ref(true);
const isDialogOpen = ref(false);
const isBusy = computed(() => props.isProcessing);

const handleAlertClose = () => {
    isDialogOpen.value = false;
};

const isFormValidated = () => {
    if (!form.amount || Number(form.amount) <= 0) {
        toast.error('Please enter a valid invoice amount.');
        return false;
    }
    if (!form.invoice_date) {
        toast.error('Please select an invoice date.');
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
            invoice_date: normalizeDate(form.invoice_date),
        });
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
};

onMounted(() => {
    isLoading.value = false;
});
</script>

<template>
    <FormCard :loading="isProcessing" size="md">
        <form @submit.prevent class="space-y-4 mt-4">
            <BaseField legend="Add Invoice" description="Register a previous invoice to this customer account">
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
                                            class="text-xs uppercase tracking-wide text-muted-foreground font-medium">Account</span>
                                        <p class="text-sm font-semibold truncate">{{ account?.account_name ?? '—' }}</p>
                                    </div>
                                </div>
                            </Field>

                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Reference No.:</FieldLabel>
                                <Input v-model="form.reference_no" placeholder="e.g. INV-001" />
                            </Field>

                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Invoice Date:</FieldLabel>
                                <BaseDatePick v-model="form.invoice_date" />
                            </Field>

                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Amount:</FieldLabel>
                                <Input v-model.number="form.amount" type="number" min="0.01" step="0.01"
                                    placeholder="0.00" required />
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
            <BaseButton type="button" :disabled="isBusy" @click="openConfirmDialog" transactionType="create"
                :loading="isProcessing" :skeleton="isLoading" />
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" transaction-type="create"
            @cancel="handleAlertClose" @confirm="handleSubmit" />
    </FormCard>
</template>
