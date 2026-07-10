<script setup>
import POSForm from './POSForm.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);

const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.post('/pos', formData, {
        preserveScroll: 'errors',
        preserveState: 'errors',
        onSuccess: () => {
            toast.success('Success', { description: 'POS transaction saved successfully!' });
            emit('form-closed');
        },
        onError: (errors) => {
            const firstKey = Object.keys(errors)[0];
            toast.warning('Failed to save transaction.', { description: errors[firstKey] });
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <POSForm @handleSubmit="handleSubmit" @form-closed="emit('form-closed')" :is-processing="isProcessing"
        card-title="New POS Transaction" transaction-type="create" />
</template>
