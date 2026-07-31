<script setup>
import SalesAgentForm from './SalesAgentForm.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';

const emit = defineEmits(['form-closed', 'sales-agent-created']);

const handleClose = () => {
    emit('form-closed');
};

const isProcessing = ref(false);
const handleSubmit = async (formData) => {
    isProcessing.value = true;
    try {
        const res = await axios.post('/sales-agents', formData);
        toast.success('Success', { description: 'Sales agent created successfully!' });
        emit('sales-agent-created', res.data.sales_agent);
        emit('form-closed');
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to create sales agent.', { description: errors[firstErrorKey][0] });
        } else {
            toast.error('Failed to create sales agent.');
        }
    } finally {
        isProcessing.value = false;
    }
};
</script>

<template>
    <div>
        <SalesAgentForm @handleSubmit="handleSubmit" @form-closed="handleClose" :is-processing="isProcessing"
            :card-title="'New Sales Agent'" :transaction-type="'create'" />
    </div>
</template>
