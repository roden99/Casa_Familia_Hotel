<script setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';

import CreateCarryItem from '@/pages/CarryItems/CreateCarryItem.vue';
import ReturnCarryItem from '@/pages/CarryItems/ReturnCarryItem.vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Carry Items', href: '/carry-items' },
];

const props = defineProps({
    carryItems: { required: true },
    columns: { type: Array, required: true },
});

const selectOptions = props.columns
    .filter(col => col.isParameter === true)
    .map(s => ({ value: s.accessorKey, label: s.header }));

const selectModelValue = ref(selectOptions.length > 0 ? selectOptions[0].value : '');

const transformedColumns = computed(() => props.columns.filter(col => col.isVisible === true));

const showCreateModal = ref(false);
const showReturnModal = ref(false);
const selectedDetail = ref(null);

const handleAction = ({ type, data }) => {
    if (type === 'return') {
        selectedDetail.value = data;
        showReturnModal.value = true;
    }
};
</script>

<template>

    <Head title="Carry Items" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="CarryItems" :data="carryItems" :columnDefs="transformedColumns"
                :selectOptions="selectOptions" v-model:selectModelValue="selectModelValue" @action="handleAction"
                :hover-fields="[
                    { field: 'sales_agent_name', label: 'Sales Agent' },
                    { field: 'product_name', label: 'Product' },
                    { field: 'lot_number', label: 'Lot No.' },
                    { field: 'expiry_date', label: 'Expiry' },
                    { field: 'quantity', label: 'Qty' },
                    { field: 'carry_date', label: 'Carry Date' },
                ]">
                <Button variant="default" class="mr-2" @click="showCreateModal = true">
                    New Carry Items
                </Button>
            </BaseIndex>

            <CreateCarryItem v-if="showCreateModal" @form-closed="showCreateModal = false" />

            <ReturnCarryItem v-if="showReturnModal && selectedDetail" :carry-detail="selectedDetail"
                @form-closed="showReturnModal = false" />
        </div>
    </AppLayout>
</template>
