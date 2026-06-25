<script setup>
import DeliveryViewForm from './DeliveryViewForm.vue';

const props = defineProps({
    delivery: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['form-closed']);
</script>

<template>
    <div>
        <DeliveryViewForm :delivery="delivery" @form-closed="emit('form-closed')" />
    </div>
</template>
