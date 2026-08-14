<script setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';

import CreatePOSItem from '@/pages/POS/CreatePOSItem.vue';
import UpdatePOSItem from '@/pages/POS/UpdatePOSItem.vue';
import DeletePOSItem from '@/pages/POS/DeletePOSItem.vue';
import ViewPOSItem from '@/pages/POS/ViewPOSItem.vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'POS Items', href: '/pos-items' },
];

const props = defineProps({
    products: { required: true },
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
const showUpdateModal = ref(false);
const showDeleteModal = ref(false);
const showViewModal = ref(false);
const selectedProduct = ref(null);

const handleAction = ({ type, data }) => {
    selectedProduct.value = data;
    if (type === 'view') showViewModal.value = true;
    if (type === 'edit') showUpdateModal.value = true;
    if (type === 'delete') showDeleteModal.value = true;
};
</script>

<template>

    <Head title="POS Items" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="PosItems" :data="products" :columnDefs="transformedColumns"
                :selectOptions="selectOptions" v-model:selectModelValue="selectModelValue" @action="handleAction">

                <Button variant="default" class="mr-2" @click="showCreateModal = true">
                    New Item
                </Button>
            </BaseIndex>

            <CreatePOSItem v-if="showCreateModal" @form-closed="showCreateModal = false" />

            <ViewPOSItem v-if="showViewModal && selectedProduct" :product="selectedProduct"
                @form-closed="showViewModal = false" />

            <UpdatePOSItem v-if="showUpdateModal && selectedProduct" :product="selectedProduct"
                @form-closed="showUpdateModal = false" />

            <DeletePOSItem v-if="showDeleteModal && selectedProduct" :product="selectedProduct"
                @form-closed="showDeleteModal = false" />
        </div>
    </AppLayout>
</template>
