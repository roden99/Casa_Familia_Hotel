<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import { Input } from '@/components/ui/input';
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
    forward_balance: props.account?.forward_balance ?? 0,
    forward_balance_date: null,
});

const isLoading = ref(true);
const isDialogOpen = ref(false);
const isBusy = computed(() => props.isProcessing);

const handleAlertClose = () => {
    isDialogOpen.value = false;
};

const isFormValidated = () => {
    if (form.forward_balance === '' || Number(form.forward_balance) < 0) {
        toast.error('Please enter a valid forward balance amount.');
        return false;
    }
    if (!form.forward_balance_date) {
        toast.error('Please select a balance date.');
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
            forward_balance_date: normalizeDate(form.forward_balance_date),
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
            <BaseField legend="Forward Balance" description="Set the carried-forward opening balance for this account">
                <template #fields>
                    <FieldGroup :skeleton="isLoading" :skeleton-rows="1" :skeleton-cols="1">
                        <div class="grid w-full grid-cols-12 gap-4">

                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Customer:</FieldLabel>
                                <span class="text-sm font-medium">{{ account?.display_name ?? '—' }}</span>
                            </Field>

                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Account:</FieldLabel>
                                <span class="text-sm font-medium">{{ account?.account_name ?? '—' }}</span>
                            </Field>

                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Forward Balance:</FieldLabel>
                                <Input v-model.number="form.forward_balance" type="number" min="0" step="0.01"
                                    placeholder="0.00" required />
                            </Field>

                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Balance Date:</FieldLabel>
                                <BaseDatePick v-model="form.forward_balance_date" />
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
