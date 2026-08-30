<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import CreateSupplier from '@/pages/Suppliers/CreateSupplier.vue';
import UpdateSupplier from '@/pages/Suppliers/UpdateSupplier.vue';
import DeleteSupplier from '@/pages/Suppliers/DeleteSupplier.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'POS Suppliers', href: '/pos-suppliers' },
];

const props = defineProps({
    suppliers: { required: true },
    columns:   { type: Array, required: true },
});

const selectOptions = props.columns.filter(col => col.isParameter === true).map(s => ({
    value: s.accessorKey,
    label: s.header,
}));
const selectModelValue = ref(selectOptions.length > 0 ? selectOptions[0].value : '');

const showCreateModal  = ref(false);
const showUpdateModal  = ref(false);
const showDeleteModal  = ref(false);
const selectedSupplier = ref(null);

const handleAction = ({ type, data }) => {
    switch (type) {
        case 'edit':
            selectedSupplier.value = data;
            showUpdateModal.value = true;
            break;
        case 'delete':
            selectedSupplier.value = data;
            showDeleteModal.value = true;
            break;
    }
};

const formattedSuppliers = computed(() => {
    const isArray = Array.isArray(props.suppliers);
    const list = isArray ? props.suppliers : props.suppliers?.data || [];
    const formatted = list.map(s => ({
        ...s,
        representative: `${s.firstname || ''} ${s.middlename || ''} ${s.lastname || ''}`.trim(),
    }));
    if (!isArray && props.suppliers?.data) return { ...props.suppliers, data: formatted };
    return formatted;
});
</script>

<template>
    <Head title="POS Suppliers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex
                IndexType="Suppliers"
                :data="formattedSuppliers"
                :columnDefs="columns.filter(col => col.isVisible === true)"
                :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue"
                @action="handleAction"
            >
                <button
                    class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90"
                    @click="showCreateModal = true"
                >
                    New POS Supplier
                </button>
            </BaseIndex>

            <CreateSupplier
                v-if="showCreateModal"
                :is-pos-module="true"
                @form-closed="showCreateModal = false"
                @member-form-closed="() => { showCreateModal = false; router.reload({ preserveScroll: true }); }"
            />

            <UpdateSupplier
                v-if="showUpdateModal"
                :supplier="selectedSupplier"
                :is-pos-module="true"
                @form-closed="showUpdateModal = false"
                @member-form-closed="() => { showUpdateModal = false; router.reload({ preserveScroll: true }); }"
            />

            <DeleteSupplier
                v-if="showDeleteModal"
                :supplier="selectedSupplier"
                @form-closed="showDeleteModal = false"
                @member-form-closed="() => { showDeleteModal = false; router.reload({ preserveScroll: true }); }"
            />
        </div>
    </AppLayout>
</template>
