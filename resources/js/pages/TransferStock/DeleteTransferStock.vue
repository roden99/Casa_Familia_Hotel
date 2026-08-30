<script setup>
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const props = defineProps({
    transfer: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);
const isDialogOpen = ref(true);

const handleConfirm = () => {
    isProcessing.value = true;
    router.delete(`/transfer-stocks/${props.transfer.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Success', { description: 'Transfer deleted successfully!' });
            emit('form-closed');
        },
        onError: () => {
            toast.error('Failed to delete transfer.');
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <FormCard :loading="isProcessing" size="sm">
        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" transaction-type="delete"
            @cancel="emit('form-closed')" @confirm="handleConfirm" />
    </FormCard>
</template>
