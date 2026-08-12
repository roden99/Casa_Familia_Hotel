<script setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';

import CreatePOSItem from '@/pages/POS/CreatePOSItem.vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'POS Items', href: '/pos-items' },
];

const props = defineProps({
    products: { required: true },
    columns:  { type: Array, required: true },
});

const selectOptions = props.columns
    .filter(col => col.isParameter === true)
    .map(s => ({ value: s.accessorKey, label: s.header }));

const selectModelValue = ref(selectOptions.length > 0 ? selectOptions[0].value : '');

const transformedColumns = computed(() =>
    props.columns.filter(col => col.isVisible === true)
);

const showCreateModal = ref(false);
</script>

<template>
    <Head title="POS Items" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="PosItems" :data="products" :columnDefs="transformedColumns"
                :selectOptions="selectOptions" v-model:selectModelValue="selectModelValue">

                <Button variant="default" class="mr-2" @click="showCreateModal = true">
                    New Item
                </Button>
            </BaseIndex>

            <CreatePOSItem v-if="showCreateModal" @form-closed="showCreateModal = false" />
        </div>
    </AppLayout>
</template>
