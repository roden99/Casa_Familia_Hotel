<script setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { router, Head } from '@inertiajs/vue3';

import CreateSalesAgent from '@/pages/SalesAgents/CreateSalesAgent.vue';
import UpdateSalesAgent from '@/pages/SalesAgents/UpdateSalesAgent.vue';
import DeleteSalesAgent from '@/pages/SalesAgents/DeleteSalesAgent.vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Sales Agent List', href: '/sales-agents' },
];

const props = defineProps({
    salesAgents: { required: true },
    columns: { type: Array, required: true },
});

const selectOptions = props.columns
    .filter(col => col.isParameter === true)
    .map(s => ({ value: s.accessorKey, label: s.header }));

const selectModelValue = ref(selectOptions.length > 0 ? selectOptions[0].value : '');

const showCreateModal = ref(false);
const showUpdateModal = ref(false);
const showDeleteModal = ref(false);
const selectedSalesAgent = ref(null);

const handleAction = ({ type, data }) => {
    switch (type) {
        case 'edit':
            selectedSalesAgent.value = data;
            showUpdateModal.value = true;
            break;
        case 'delete':
            selectedSalesAgent.value = data;
            showDeleteModal.value = true;
            break;
        default:
            break;
    }
};
</script>

<template>

    <Head title="Sales Agents" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="Sales Agents" :data="props.salesAgents"
                :columnDefs="columns.filter(col => col.isVisible === true)" :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'name', label: 'Name' },
                    { field: 'email', label: 'Email' },
                    { field: 'phone', label: 'Phone' },
                ]">
                <Button variant="default" class="mr-2" @click="showCreateModal = true">
                    New Sales Agent
                </Button>
            </BaseIndex>

            <CreateSalesAgent v-if="showCreateModal" @form-closed="showCreateModal = false"
                @sales-agent-created="() => { showCreateModal = false; router.reload(); }" />

            <UpdateSalesAgent v-if="showUpdateModal && selectedSalesAgent" :sales-agent="selectedSalesAgent"
                @sales-agent-form-closed="showUpdateModal = false" />

            <DeleteSalesAgent v-if="showDeleteModal && selectedSalesAgent" :sales-agent="selectedSalesAgent"
                @sales-agent-form-closed="showDeleteModal = false" />
        </div>
    </AppLayout>
</template>
