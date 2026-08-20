<script setup>
import ReturnGoodStockCreateForm from './ReturnGoodStockCreateForm.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);

const handleSubmit = async (formData) => {
    isProcessing.value = true;
    try {
        await axios.post('/return-good-stocks', formData, {
            headers: { Accept: 'application/json' },
        });
        toast.success('Return Good Stock recorded successfully.');
        router.reload({ only: ['records'] });
        emit('form-closed');
    } catch (error) {
        const errors = error.response?.data?.errors;
        const first = errors ? Object.values(errors)[0][0] : 'Failed to save RGS.';
        toast.error(first);
        isProcessing.value = false;
    }
};
</script>

<template>
    <ReturnGoodStockCreateForm @handleSubmit="handleSubmit" @form-closed="emit('form-closed')"
        :is-processing="isProcessing" />
</template>
