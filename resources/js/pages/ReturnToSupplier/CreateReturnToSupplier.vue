<script setup>
import ReturnToSupplierForm from './ReturnToSupplierForm.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);

const handleSubmit = async (formData) => {
    isProcessing.value = true;
    try {
        await axios.post('/return-to-suppliers', formData, {
            headers: { Accept: 'application/json' },
        });
        toast.success('Return to supplier recorded successfully.');
        router.reload({ only: ['records'] });
        emit('form-closed');
    } catch (error) {
        const errors = error.response?.data?.errors;
        const first = errors ? Object.values(errors)[0][0] : 'Failed to save return.';
        toast.error(first);
        isProcessing.value = false;
    }
};
</script>

<template>
    <ReturnToSupplierForm @handleSubmit="handleSubmit" @form-closed="emit('form-closed')"
        :is-processing="isProcessing" />
</template>
