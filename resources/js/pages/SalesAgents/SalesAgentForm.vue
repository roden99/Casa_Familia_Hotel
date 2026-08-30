<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';

const props = defineProps({
    isProcessing: {
        type: Boolean,
        default: false,
    },
    cardTitle: {
        type: String,
        default: 'Form',
    },
    salesAgent: {
        type: Object,
        default: null,
    },
    transactionType: {
        type: String,
        default: 'create',
    },
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const form = useForm({
    name: props.salesAgent?.name || '',
    email: props.salesAgent?.email || '',
    phone: props.salesAgent?.phone || '',
});

const isLoading = ref(true);
const isDialogOpen = ref(false);
const isBusy = computed(() => props.isProcessing);

const handleAlertClose = () => {
    isDialogOpen.value = false;
    if (props.transactionType === 'delete') {
        emit('form-closed');
    }
};

const isFormValidated = () => {
    if (!form.name.toString().trim()) {
        toast.error('Fill up the forms properly');
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
        emit('handleSubmit', form.data());
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
};

const closeDialog = () => {
    isDialogOpen.value = false;
};

defineExpose({ closeDialog });

onMounted(() => {
    if (props.transactionType === 'delete') {
        isDialogOpen.value = true;
    }
    isLoading.value = false;
});
</script>

<template>
    <FormCard :loading="isProcessing" size="md">
        <form @submit.prevent class="space-y-4 mt-4">
            <BaseField legend="Sales Agent Information" description="Enter sales agent details">
                <template #fields>
                    <FieldGroup :skeleton="isLoading" :skeleton-rows="3" :skeleton-cols="1">
                        <div class="grid w-full grid-cols-12 gap-4">
                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Name:</FieldLabel>
                                <Input v-model="form.name" required />
                            </Field>
                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Email:</FieldLabel>
                                <Input v-model="form.email" type="email" />
                            </Field>
                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Phone:</FieldLabel>
                                <Input v-model="form.phone" />
                            </Field>
                        </div>
                    </FieldGroup>
                </template>
            </BaseField>
        </form>
        <template #footer>
            <BaseButton type="button" :disabled="isBusy" @click="emit('form-closed')" transactionType="cancel"
                :skeleton="isLoading" />
            <BaseButton type="button" :disabled="isBusy" @click="openConfirmDialog"
                :transactionType="props.transactionType" :skeleton="isLoading" />
        </template>
        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" :transaction-type="props.transactionType"
            @cancel="handleAlertClose" @confirm="handleSubmit" />
    </FormCard>
</template>
