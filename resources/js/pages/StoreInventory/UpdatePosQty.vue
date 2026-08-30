<script setup>
import UpdatePosQtyForm from './UpdatePosQtyForm.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);

const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.post('/store-inventory/bulk-init-pos-qty', formData, {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            toast.success('Success', { description: 'POS quantities initialized successfully!' });
            emit('form-closed');
        },
        onError: (errors) => {
            const firstKey = Object.keys(errors)[0];
            toast.warning('Failed to initialize.', { description: errors[firstKey] });
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <UpdatePosQtyForm :is-processing="isProcessing" @handleSubmit="handleSubmit" @form-closed="emit('form-closed')" />
</template>
