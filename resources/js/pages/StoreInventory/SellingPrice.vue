<script setup>
import SellingPriceForm from './SellingPriceForm.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    product: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);

const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.patch(`/store-inventory/${props.product.id}/selling-price`, formData, {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            toast.success('Success', { description: 'Selling price updated successfully!' });
            emit('form-closed');
        },
        onError: (errors) => {
            const firstKey = Object.keys(errors)[0];
            toast.warning('Failed to update.', { description: errors[firstKey] });
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <SellingPriceForm :product="product" :is-processing="isProcessing" @handleSubmit="handleSubmit"
        @form-closed="emit('form-closed')" />
</template>
