<script setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';

import CreatePosDelivery from '@/pages/PosDelivery/CreatePosDelivery.vue';
import DeletePosDelivery from '@/pages/PosDelivery/DeletePosDelivery.vue';
import ViewPosDelivery   from '@/pages/PosDelivery/ViewPosDelivery.vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'POS Deliveries', href: '/pos-deliveries' },
];

const props = defineProps({
    deliveries: { required: true },
    columns:    { type: Array, required: true },
    suppliers:  { type: Array, default: () => [] },
});

const selectOptions = props.columns
    .filter(col => col.isParameter === true)
    .map(s => ({ value: s.accessorKey, label: s.header }));

const selectModelValue = ref(selectOptions.length > 0 ? selectOptions[0].value : '');

const transformedColumns = computed(() =>
    props.columns.filter(col => col.isVisible === true)
);

const showCreateModal   = ref(false);
const showDeleteModal   = ref(false);
const showViewModal     = ref(false);
const selectedDelivery  = ref(null);

const handleAction = ({ type, data }) => {
    selectedDelivery.value = data;
    if (type === 'view')   showViewModal.value   = true;
    if (type === 'delete') showDeleteModal.value = true;
};
</script>

<template>

    <Head title="POS Deliveries" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="PosDelivery" :data="deliveries" :columnDefs="transformedColumns"
                :selectOptions="selectOptions" v-model:selectModelValue="selectModelValue"
                @action="handleAction">

                <Button variant="default" class="mr-2" @click="showCreateModal = true">
                    New Delivery
                </Button>
            </BaseIndex>

            <CreatePosDelivery v-if="showCreateModal" :suppliers="suppliers"
                @form-closed="showCreateModal = false" />

            <ViewPosDelivery v-if="showViewModal" :delivery="selectedDelivery"
                @form-closed="showViewModal = false" />

            <DeletePosDelivery v-if="showDeleteModal" :delivery="selectedDelivery"
                @form-closed="showDeleteModal = false" />
        </div>
    </AppLayout>
</template>
