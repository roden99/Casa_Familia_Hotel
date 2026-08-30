<script setup>
import CustomerAccountInvoiceEditForm from './CustomerAccountInvoiceEditForm.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    account: {
        type: Object,
        required: true,
    },
    invoice: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);

const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.patch(`/customer-accounts/${props.account.csa_id}/invoices/${props.invoice.invoice_id}`, formData, {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            toast.success('Success', { description: 'Invoice updated successfully!' });
            emit('form-closed');
        },
        onError: (errors) => {
            const firstKey = Object.keys(errors)[0];
            toast.warning('Update failed.', { description: errors[firstKey] });
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <div>
        <CustomerAccountInvoiceEditForm :account="account" :invoice="invoice" :is-processing="isProcessing"
            @handleSubmit="handleSubmit" @form-closed="emit('form-closed')" />
    </div>
</template>
