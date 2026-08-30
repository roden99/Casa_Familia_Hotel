<script setup>
import UserForm from './UserForm.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);

const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.post('/users', formData, {
        preserveScroll: 'errors',
        preserveState:  'errors',
        onSuccess: () => {
            toast.success('Success', { description: 'User created successfully!' });
            emit('form-closed');
        },
        onError: (errors) => {
            const firstKey = Object.keys(errors)[0];
            toast.warning('Failed to create user.', { description: errors[firstKey] });
        },
        onFinish: () => { isProcessing.value = false; },
    });
};
</script>

<template>
    <UserForm transaction-type="create" :is-processing="isProcessing"
        @handleSubmit="handleSubmit" @form-closed="emit('form-closed')" />
</template>
