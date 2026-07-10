<script setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';

import CreatePOS from '@/pages/POS/CreatePOS.vue';
import DeletePOS from '@/pages/POS/DeletePOS.vue';
import ViewPOS from '@/pages/POS/ViewPOS.vue';

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
const selectedTransaction = ref(null);

const handleAction = ({ type, data }) => {
    switch (type) {
        case 'view':
            selectedTransaction.value = data;
            showViewModal.value = true;
            break;
        case 'delete':
            selectedTransaction.value = data;
            showDeleteModal.value = true;
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
        </div>
    </AppLayout>
</template>
