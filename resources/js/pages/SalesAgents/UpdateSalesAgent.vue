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

const handleClose = () => {
    emit('sales-agent-form-closed');
};

const isProcessing = ref(false);
const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.put(`/sales-agents/${props.salesAgent.id}`, formData, {
        preserveScroll: 'errors',
        preserveState: 'errors',
        onSuccess: () => {
            toast.success('Success', { description: 'Sales agent updated successfully!' });
            isProcessing.value = false;
            emit('sales-agent-form-closed');
        },
        onError: (errors) => {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to update sales agent.', { description: errors[firstErrorKey] });
            isProcessing.value = false;
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <div>
        <SalesAgentForm @handleSubmit="handleSubmit" @form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'Update Sales Agent'" :transaction-type="'update'" :sales-agent="salesAgent" />
    </div>
</template>
