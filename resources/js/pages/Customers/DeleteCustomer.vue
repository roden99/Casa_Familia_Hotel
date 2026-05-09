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

const emit = defineEmits(['member-form-closed', 'customer-deleted']);

const formRef = ref(null);

const handleClose = () => {
    emit('member-form-closed');
};

const handleSubmit = async () => {
    try {
        await axios.delete(`/customers/${props.customer.id}`);
        formRef.value?.closeDialog();
        emit('customer-deleted');
        emit('member-form-closed');
        toast.success('Success', { description: 'Customer deactivated successfully!' });
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            const firstErrorKey = Object.keys(errors)[0];
            toast.warning('Failed to deactivate customer.', { description: errors[firstErrorKey][0] });
        } else {
            toast.error('Failed to deactivate customer.');
        }
        formRef.value?.closeDialog();
        emit('member-form-closed');
    } finally {

    }
};
</script>

<template>
    <div>
        <CustomerForm ref="formRef" @handleSubmit="handleSubmit" @member-form-closed="handleClose"
            :card-title="'Delete Customer'" :transaction-type="'delete'" :customer="customer" />
    </div>
</template>
