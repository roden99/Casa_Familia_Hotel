<script setup>
import CustomerAccountForwardBalanceForm from './CustomerAccountForwardBalanceForm.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    account: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);

const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.patch(`/customer-accounts/${props.account.csa_id}/forward-balance`, formData, {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            toast.success('Success', { description: 'Forward balance set successfully!' });
            emit('form-closed');
        },
        onError: (errors) => {
            const firstKey = Object.keys(errors)[0];
            toast.warning('Failed to set forward balance.', { description: errors[firstKey] });
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <div>
        <CustomerAccountForwardBalanceForm :account="account" :is-processing="isProcessing" @handleSubmit="handleSubmit"
            @form-closed="emit('form-closed')" />
    </div>
</template>
