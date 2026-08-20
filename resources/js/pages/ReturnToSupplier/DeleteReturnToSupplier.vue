<script setup>
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';

const props = defineProps({
    record: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isDeleting = ref(false);

const handleConfirm = async () => {
    isDeleting.value = true;
    try {
        await axios.delete(`/return-to-suppliers/${props.record.id}`, {
            headers: { Accept: 'application/json' },
        });
        toast.success(`RTS #${props.record.id} deleted and inventory reversed.`);
        router.reload({ only: ['records'] });
        emit('form-closed');
    } catch {
        toast.error('Failed to delete return record.');
        isDeleting.value = false;
    }
};
</script>

<template>
    <BaseAlertDialog :open="true" :loading="isDeleting" transaction-type="delete" @cancel="emit('form-closed')"
        @confirm="handleConfirm" />
</template>
