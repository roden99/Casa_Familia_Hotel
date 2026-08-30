<script setup>
import EditReturnGoodStockForm from './EditReturnGoodStockForm.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps({
    record: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);

const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.put(`/return-good-stocks/${props.record.id}`, formData, {
        preserveScroll: 'errors',
        preserveState: 'errors',
        onSuccess: () => {
            toast.success('Return Good Stock updated successfully!');
            isProcessing.value = false;
            emit('form-closed');
        },
        onError: (errors) => {
            const firstKey = Object.keys(errors)[0];
            toast.warning('Failed to update RGS.', { description: errors[firstKey] });
            isProcessing.value = false;
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <div>
        <EditReturnGoodStockForm :record="record" :is-processing="isProcessing" @handleSubmit="handleSubmit"
            @form-closed="emit('form-closed')" />
    </div>
</template>
