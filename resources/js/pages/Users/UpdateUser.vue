<script setup>
import UserForm from './UserForm.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    user: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);

const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.put(`/users/${props.user.id}`, formData, {
        preserveScroll: 'errors',
        preserveState:  'errors',
        onSuccess: () => {
            toast.success('Success', { description: 'User updated successfully!' });
            emit('form-closed');
        },
        onError: (errors) => {
            const firstKey = Object.keys(errors)[0];
            toast.warning('Failed to update user.', { description: errors[firstKey] });
        },
        onFinish: () => { isProcessing.value = false; },
    });
};
</script>

<template>
    <UserForm transaction-type="update" :user="user" :is-processing="isProcessing"
        @handleSubmit="handleSubmit" @form-closed="emit('form-closed')" />
</template>
