<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';

import ViewReturnGoodStock from '@/pages/ReturnGoodStock/ViewReturnGoodStock.vue';
import PrintReturnGoodStock from '@/pages/ReturnGoodStock/PrintReturnGoodStock.vue';
import EditReturnGoodStock from '@/pages/ReturnGoodStock/EditReturnGoodStock.vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Return Good Stock', href: '/return-good-stocks' },
];

const props = defineProps({
    records: { required: true },
    columns: { type: Array, required: true },
});

const selectOptions = props.columns
    .filter(col => col.isParameter === true)
    .map(s => ({ value: s.accessorKey, label: s.header }));

const selectModelValue = ref(selectOptions.length > 0 ? selectOptions[0].value : '');

const transformedColumns = computed(() =>
    props.columns.filter(col => col.isVisible === true)
);

const showViewModal = ref(false);
const showPrintModal = ref(false);
const showEditModal = ref(false);
const selectedRecord = ref(null);

const handleAction = ({ type, data }) => {
    switch (type) {
        case 'view':
            selectedRecord.value = data;
            showViewModal.value = true;
            break;
        case 'print':
            selectedRecord.value = data;
            showPrintModal.value = true;
            break;
        case 'edit':
            selectedRecord.value = data;
            showEditModal.value = true;
            break;
        default:
            break;
    }
};
</script>

<template>

    <Head title="Return Good Stock" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="ReturnGoodStock" :data="records" :columnDefs="transformedColumns"
                :selectOptions="selectOptions" v-model:selectModelValue="selectModelValue" @action="handleAction" />

            <ViewReturnGoodStock v-if="showViewModal" :record="selectedRecord" @form-closed="showViewModal = false" />
            <PrintReturnGoodStock v-if="showPrintModal" :record="selectedRecord"
                @form-closed="showPrintModal = false" />
            <EditReturnGoodStock v-if="showEditModal" :record="selectedRecord" @form-closed="showEditModal = false" />
        </div>
    </AppLayout>
</template>
