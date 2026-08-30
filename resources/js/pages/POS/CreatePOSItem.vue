<script setup>
import POSItemForm from './POSItemForm.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';

const emit = defineEmits(['form-closed', 'item-created']);

const isProcessing = ref(false);

const handleSubmit = async (formData) => {
    isProcessing.value = true;
    try {
        const res = await axios.post('/pos-items', formData);
        toast.success('Success', { description: 'POS item created successfully!' });
        emit('item-created', res.data.product);
        emit('form-closed');
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            const firstKey = Object.keys(errors)[0];
            toast.warning('Failed to create item.', { description: errors[firstKey][0] });
        } else {
            toast.error('Failed to create POS item.');
        }
    } finally {
        isProcessing.value = false;
    }
};
</script>

<template>
    <POSItemForm :is-processing="isProcessing"
        @handleSubmit="handleSubmit" @form-closed="emit('form-closed')" />
</template>
