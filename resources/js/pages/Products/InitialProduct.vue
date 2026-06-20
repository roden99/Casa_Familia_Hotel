<script setup>
import InitialProductForm from './InitialProductForm.vue';
import { ref, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['form-closed']);

const isProcessing = ref(false);

onMounted(() => {
    if (props.product?.is_inventory) {
        toast.warning('Already initialized', {
            description: `${props.product.display_name ?? props.product.productname} already has an initial inventory.`,
        });
        emit('form-closed');
    }
});

const handleSubmit = (formData) => {
    isProcessing.value = true;
    router.patch(`/products/${props.product.id}/initial-inventory`, formData, {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            toast.success('Success', { description: 'Initial inventory set successfully!' });
            emit('form-closed');
        },
        onError: (errors) => {
            const firstKey = Object.keys(errors)[0];
            toast.warning('Failed to set inventory.', { description: errors[firstKey] });
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <div>
        <InitialProductForm :product="product" :is-processing="isProcessing" :card-title="'Initial Inventory'"
            transaction-type="create" @handleSubmit="handleSubmit" @form-closed="emit('form-closed')" />
    </div>
</template>
