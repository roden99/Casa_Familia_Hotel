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

const handleSubmit = async () => {
    isProcessing.value = true;
    try {
        await axios.delete(`/pos-items/${props.product.id}`);
        toast.success('Success', { description: 'POS item deleted successfully!' });
        emit('form-closed');
    } catch (error) {
        toast.error('Failed to delete POS item.');
    } finally {
        isProcessing.value = false;
    }
};
</script>

<template>
    <POSItemForm :product="product" transaction-type="delete" :is-processing="isProcessing" @handleSubmit="handleSubmit"
        @form-closed="emit('form-closed')" />
</template>
