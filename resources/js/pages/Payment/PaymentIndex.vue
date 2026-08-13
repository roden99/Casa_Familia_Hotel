<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import PaymentDetails from './PaymentDetails.vue';
import { Head } from '@inertiajs/vue3';
import { computed, h, ref } from 'vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Payments', href: '/payments' },
];

const props = defineProps({
    payments: { type: Array, required: true },
    columns:  { type: Array, required: true },
});

const showDetailsModal = ref(false);
const selectedPayment = ref(null);

const handleAction = ({ type, data }) => {
    if (type === 'payment_details') {
        selectedPayment.value = data;
        showDetailsModal.value = true;
    }
};

const enrichedColumns = computed(() =>
    props.columns.map(col => {
        if (col.accessorKey === 'type') {
            return {
                ...col,
                cell: ({ row }) => {
                    const val = row.original.type;
                    const isInvoice = val === 'Invoice';
                    return h('span', {
                        class: `inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${
                            isInvoice
                                ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'
                                : 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300'
                        }`,
                    }, val);
                },
            };
        }
        if (col.accessorKey === 'tag_status') {
            return {
                ...col,
                cell: ({ row }) => {
                    const val = row.original.tag_status;
                    const isTagged = val === 'tagged';
                    return h('span', {
                        class: `inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${
                            isTagged
                                ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
                        }`,
                    }, isTagged ? 'Tagged' : 'Untagged');
                },
            };
        }
        return col;
    })
);
</script>

<template>
    <Head title="Payments" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex
                IndexType="Payments"
                :data="props.payments"
                :columnDefs="enrichedColumns.filter(col => col.isVisible)"
                @action="handleAction"
            />

            <PaymentDetails
                v-if="showDetailsModal"
                :payment="selectedPayment"
                @form-closed="showDetailsModal = false"
            />
        </div>
    </AppLayout>
</template>
