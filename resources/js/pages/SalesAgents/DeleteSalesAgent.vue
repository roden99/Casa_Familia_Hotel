<script setup>
import SalesAgentForm from './SalesAgentForm.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps({
    salesAgent: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['sales-agent-form-closed']);
const formRef = ref(null);

const handleClose = () => {
    emit('sales-agent-form-closed');
};

const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.delete(`/sales-agents/${props.salesAgent.id}`, {
        preserveScroll: 'errors',
        preserveState: 'errors',
        onSuccess: () => {
            toast.success('Success', { description: 'Sales agent deactivated successfully!' });
            isProcessing.value = false;
            emit('sales-agent-form-closed');
        },
        onError: (errors) => {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to deactivate sales agent.', { description: errors[firstErrorKey] });
            isProcessing.value = false;
            formRef.value?.closeDialog();
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <div>
        <SalesAgentForm ref="formRef" @handleSubmit="handleSubmit" @form-closed="handleClose"
            :is-processing="isProcessing" :card-title="'Delete Sales Agent'" :transaction-type="'delete'"
            :sales-agent="salesAgent" />
    </div>
</template>
