<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    user: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);
const isDialogOpen = ref(true);

const handleConfirm = () => {
    isProcessing.value = true;
    router.delete(`/users/${props.user.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Success', { description: 'User deleted.' });
            emit('form-closed');
        },
        onError: () => toast.error('Failed to delete user.'),
        onFinish: () => { isProcessing.value = false; },
    });
};
</script>

<template>
    <FormCard :loading="false" size="sm">
        <div class="py-2 text-sm">
            Delete user <span class="font-semibold">{{ user.name }}</span>? This cannot be undone.
        </div>
        <template #footer>
            <BaseButton type="button" transactionType="cancel" @click="emit('form-closed')" />
            <BaseButton type="button" transactionType="delete" :loading="isProcessing" :disabled="isProcessing"
                @click="handleConfirm" />
        </template>
        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" transaction-type="delete"
            @cancel="emit('form-closed')" @confirm="handleConfirm" />
    </FormCard>
</template>
