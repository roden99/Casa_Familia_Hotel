<script setup>
import POSItemForm from './POSItemForm.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';

const props = defineProps({
    product: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);

const handleSubmit = async (formData) => {
    isProcessing.value = true;
    try {
        await axios.patch(`/pos-items/${props.product.id}`, formData);
        toast.success('Success', { description: 'POS item updated successfully!' });
        emit('form-closed');
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            const firstKey = Object.keys(errors)[0];
            toast.warning('Failed to update item.', { description: errors[firstKey][0] });
        } else {
            toast.error('Failed to update POS item.');
        }
    } finally {
        isProcessing.value = false;
    }
};
</script>

<template>
    <POSItemForm :product="product" transaction-type="update" :is-processing="isProcessing" @handleSubmit="handleSubmit"
        @form-closed="emit('form-closed')" />
</template>
