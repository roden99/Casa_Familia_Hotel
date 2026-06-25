<script setup>
import SalesOrderViewForm from './SalesOrderViewForm.vue';

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['form-closed']);
</script>

<template>
    <div>
        <SalesOrderViewForm :order="order" @form-closed="emit('form-closed')" />
    </div>
</template>
