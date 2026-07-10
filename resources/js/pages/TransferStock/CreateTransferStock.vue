<script setup>
import TransferStockForm from './TransferStockForm.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);

const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.post('/transfer-stocks', formData, {
        preserveScroll: 'errors',
        preserveState: 'errors',
        onSuccess: () => {
            toast.success('Success', { description: 'Transfer created successfully!' });
            emit('form-closed');
        },
        onError: (errors) => {
            const firstKey = Object.keys(errors)[0];
            toast.warning('Failed to create transfer.', { description: errors[firstKey] });
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <TransferStockForm @handleSubmit="handleSubmit" @form-closed="emit('form-closed')" :is-processing="isProcessing"
        card-title="New Transfer Stock" transaction-type="create" />
</template>
