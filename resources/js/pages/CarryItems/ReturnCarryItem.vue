<script setup>
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const props = defineProps({
    carryDetail: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);
const isDialogOpen = ref(true);

const handleConfirm = () => {
    isProcessing.value = true;
    router.patch(`/carry-item-details/${props.carryDetail.id}/return`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Success', { description: 'Item returned to inventory.' });
            emit('form-closed');
        },
        onError: () => {
            toast.error('Failed to return item to inventory.');
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
