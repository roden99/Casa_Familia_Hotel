<script setup>
import CustomerAccountInvoiceForm from './CustomerAccountInvoiceForm.vue';
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
    router.post(`/customer-accounts/${props.account.csa_id}/invoices`, formData, {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            toast.success('Success', { description: 'Invoice recorded successfully!' });
            emit('form-closed');
        },
        onError: (errors) => {
            const firstKey = Object.keys(errors)[0];
            toast.warning('Invoice failed.', { description: errors[firstKey] });
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <div>
        <CustomerAccountInvoiceForm :account="account" :is-processing="isProcessing" @handleSubmit="handleSubmit"
            @form-closed="emit('form-closed')" />
    </div>
</template>
