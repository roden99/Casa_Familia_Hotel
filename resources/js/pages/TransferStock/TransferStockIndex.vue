<script setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { router, Head } from '@inertiajs/vue3';

import CreateTransferStock from '@/pages/TransferStock/CreateTransferStock.vue';
import DeleteTransferStock from '@/pages/TransferStock/DeleteTransferStock.vue';
import ViewTransferStock from '@/pages/TransferStock/ViewTransferStock.vue';
import PrintTransferStock from '@/pages/TransferStock/PrintTransferStock.vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transfer Stock', href: '/transfer-stocks' },
];

const props = defineProps({
    transfers: { required: true },
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
const showViewModal   = ref(false);
const showPrintModal  = ref(false);
const selectedTransfer = ref(null);

const handleAction = ({ type, data }) => {
    switch (type) {
        case 'view':
            selectedTransfer.value = data;
            showViewModal.value = true;
            break;
        case 'print':
            selectedTransfer.value = data;
            showPrintModal.value = true;
            break;
        case 'delete':
            selectedTransfer.value = data;
            showDeleteModal.value = true;
            break;
        default:
            break;
    }
};
</script>

<template>

    <Head title="Transfer Stock" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="TransferStock" :data="transfers" :columnDefs="transformedColumns"
                :selectOptions="selectOptions" v-model:selectModelValue="selectModelValue" @action="handleAction">

                <Button variant="default" class="mr-2" @click="showCreateModal = true">
                    New Transfer
                </Button>
            </BaseIndex>

            <CreateTransferStock v-if="showCreateModal" @form-closed="showCreateModal = false" />

            <DeleteTransferStock v-if="showDeleteModal" :transfer="selectedTransfer"
                @form-closed="showDeleteModal = false" />

            <ViewTransferStock v-if="showViewModal" :transfer="selectedTransfer" @form-closed="showViewModal = false" />

            <PrintTransferStock v-if="showPrintModal" :transfer="selectedTransfer" @form-closed="showPrintModal = false" />
        </div>
    </AppLayout>
</template>
