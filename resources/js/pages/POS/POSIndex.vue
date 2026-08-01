<script setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';

import CreatePOS from '@/pages/POS/CreatePOS.vue';
import DeletePOS from '@/pages/POS/DeletePOS.vue';
import ViewPOS from '@/pages/POS/ViewPOS.vue';
import POSReceipt from '@/pages/POS/POSReceipt.vue';
import axios from 'axios';
import { toast } from 'vue-sonner';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'POS Transactions', href: '/pos' },
];

const props = defineProps({
    transactions: { required: true },
    columns: { type: Array, required: true },
});

const selectOptions = props.columns
    .filter(col => col.isParameter === true)
    .map(s => ({ value: s.accessorKey, label: s.header }));

const selectModelValue = ref(selectOptions.length > 0 ? selectOptions[0].value : '');

const transformedColumns = computed(() =>
    props.columns.filter(col => col.isVisible === true)
);

const showCreateModal = ref(false);
const showDeleteModal = ref(false);
const showViewModal = ref(false);
const showReprintModal = ref(false);
const selectedTransaction = ref(null);
const reprintData = ref(null);

const handleAction = async ({ type, data }) => {
    switch (type) {
        case 'view':
            selectedTransaction.value = data;
            showViewModal.value = true;
            break;
        case 'delete':
            selectedTransaction.value = data;
            showDeleteModal.value = true;
            break;
        case 'reprint':
            try {
                const res = await axios.get(`/pos/${data.id}`, { headers: { Accept: 'application/json' } });
                const tx = res.data.transaction;
                reprintData.value = {
                    sale_date: tx.sale_date,
                    receipt_no: tx.receipt_no,
                    payment_method: tx.payment_method,
                    customer_name: tx.customer_name,
                    notes: tx.notes,
                    items: res.data.items,
                };
                showReprintModal.value = true;
            } catch {
                toast.error('Failed to load receipt data.');
            }
            break;
        default:
            break;
    }
};
</script>

<template>

    <Head title="POS Transactions" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="POS" :data="transactions" :columnDefs="transformedColumns"
                :selectOptions="selectOptions" v-model:selectModelValue="selectModelValue" @action="handleAction">

                <Button variant="default" class="mr-2" @click="showCreateModal = true">
                    New Transaction
                </Button>
            </BaseIndex>

            <CreatePOS v-if="showCreateModal" @form-closed="showCreateModal = false" />

            <DeletePOS v-if="showDeleteModal" :transaction="selectedTransaction"
                @form-closed="showDeleteModal = false" />

            <ViewPOS v-if="showViewModal" :transaction="selectedTransaction" @form-closed="showViewModal = false" />

            <POSReceipt v-if="showReprintModal" :receipt="reprintData" @close="showReprintModal = false" />
        </div>
    </AppLayout>
</template>
