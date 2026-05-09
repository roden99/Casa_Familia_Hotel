<script setup>
import CustomerForm from '@/pages/Customers/CustomerForm.vue'
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import axios from 'axios'

const props = defineProps({
    customer: {
        type: Object,
        required: true,
    }
});

const emit = defineEmits(['member-form-closed', 'customer-updated']);

const formRef = ref(null);

const handleClose = () => {
    emit('member-form-closed');
};

const handleSubmit = async (formData) => {
    try {
        const res = await axios.put(`/customers/${props.customer.id}`, formData);
        toast.success('Success', { description: 'Customer updated successfully!' });
        formRef.value?.closeDialog();
        emit('customer-updated', res.data.customer);
        emit('member-form-closed');
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to update customer.', { description: errors[firstErrorKey][0] });
        } else {
            toast.error('Failed to update customer.');
        }
        formRef.value?.closeDialog();
    } finally {

    }
};
</script>

<template>
    <div>
        <CustomerForm ref="formRef" @handleSubmit="handleSubmit" @member-form-closed="handleClose"
            :card-title="'Update Customer'" :transaction-type="'update'" :customer="customer" />
    </div>
</template>
