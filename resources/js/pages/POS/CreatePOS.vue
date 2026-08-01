<script setup>
import POSForm from './POSForm.vue';
import POSReceipt from './POSReceipt.vue';
import POSPaymentDialog from './POSPaymentDialog.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);
const pendingFormData = ref(null); // holds formData while payment dialog is open
const paymentData = ref(null);    // holds formData while payment dialog is open
const receiptData = ref(null);    // holds formData + tendered/change for receipt

// Step 1: form submitted — show payment dialog before saving
const handleSubmit = (formData) => {
    pendingFormData.value = formData;
    paymentData.value = formData;
};

// Step 2: payment confirmed — now save to DB, then show receipt
const handlePaymentConfirm = async ({ tendered, change }) => {
    isProcessing.value = true;
    try {
        const res = await axios.post('/pos', pendingFormData.value, {
            headers: { Accept: 'application/json' },
        });
        toast.success('Success', { description: 'POS transaction saved successfully!' });
        receiptData.value = { ...pendingFormData.value, tendered, change, sale_date: res.data.sale_date };
        paymentData.value = null;
        pendingFormData.value = null;
        router.reload({ only: ['transactions'] });
    } catch (err) {
        const errors = err.response?.data?.errors;
        const msg = errors ? Object.values(errors)[0][0] : 'Failed to save transaction.';
        toast.warning('Failed to save transaction.', { description: msg });
        paymentData.value = null;
        pendingFormData.value = null;
    } finally {
        isProcessing.value = false;
    }
};

const cancelPayment = () => {
    paymentData.value = null;
    pendingFormData.value = null;
};

const closeReceipt = () => {
    receiptData.value = null;
    emit('form-closed');
};
</script>

<template>
    <POSForm v-if="!paymentData && !receiptData" @handleSubmit="handleSubmit" @form-closed="emit('form-closed')"
        :is-processing="isProcessing" card-title="New POS Transaction" transaction-type="create" />

    <POSPaymentDialog v-if="paymentData" :receipt="paymentData" :is-processing="isProcessing"
        @confirm="handlePaymentConfirm" @cancel="cancelPayment" />

    <POSReceipt v-if="receiptData" :receipt="receiptData" @close="closeReceipt" />
</template>
