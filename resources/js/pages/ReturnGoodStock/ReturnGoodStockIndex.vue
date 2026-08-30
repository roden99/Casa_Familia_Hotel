<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import axios from 'axios';
import { Button } from '@/components/ui/button';

import ViewReturnGoodStock from '@/pages/ReturnGoodStock/ViewReturnGoodStock.vue';
import PrintReturnGoodStock from '@/pages/ReturnGoodStock/PrintReturnGoodStock.vue';
import EditReturnGoodStock from '@/pages/ReturnGoodStock/EditReturnGoodStock.vue';
import CreateReturnGoodStock from '@/pages/ReturnGoodStock/CreateReturnGoodStock.vue';

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
const showCreateModal = ref(false);
const selectedRecord = ref(null);
const isDeletingId = ref(null);

const handleAction = async ({ type, data }) => {
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
        case 'delete':
            if (!confirm(`Delete RGS #${data.id}? This will reverse the inventory adjustments.`)) return;
            isDeletingId.value = data.id;
            try {
                await axios.delete(`/return-good-stocks/${data.id}`, {
                    headers: { Accept: 'application/json' },
                });
                toast.success(`RGS #${data.id} deleted and inventory reversed.`);
                router.reload({ only: ['records'] });
            } catch {
                toast.error('Failed to delete RGS record.');
            } finally {
                isDeletingId.value = null;
            }
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
                :selectOptions="selectOptions" v-model:selectModelValue="selectModelValue" @action="handleAction">
                <Button variant="default" class="mr-2" @click="showCreateModal = true">
                    New RGS
                </Button>
            </BaseIndex>

            <CreateReturnGoodStock v-if="showCreateModal" @form-closed="showCreateModal = false" />
            <ViewReturnGoodStock v-if="showViewModal" :record="selectedRecord" @form-closed="showViewModal = false" />
            <PrintReturnGoodStock v-if="showPrintModal" :record="selectedRecord"
                @form-closed="showPrintModal = false" />
            <EditReturnGoodStock v-if="showEditModal" :record="selectedRecord" @form-closed="showEditModal = false" />
        </div>
    </AppLayout>
</template>
