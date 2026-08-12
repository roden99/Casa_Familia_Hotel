<script setup>
import PosDeliveryForm from './PosDeliveryForm.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    suppliers: { type: Array, default: () => [] },
});

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);

const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.post('/pos-deliveries', formData, {
        preserveScroll: 'errors',
        preserveState:  'errors',
        onSuccess: () => {
            toast.success('Success', { description: 'POS delivery recorded successfully!' });
            emit('form-closed');
        },
        onError: (errors) => {
            const firstKey = Object.keys(errors)[0];
            toast.warning('Failed to save delivery.', { description: errors[firstKey] });
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <PosDeliveryForm :suppliers="suppliers" :is-processing="isProcessing"
        @handleSubmit="handleSubmit" @form-closed="emit('form-closed')" />
</template>
