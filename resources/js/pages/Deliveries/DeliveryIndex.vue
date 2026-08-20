<script setup>
import { Button, buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumb from '@/components/ui/breadcrumb/Breadcrumb.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { router, usePage, Head } from '@inertiajs/vue3';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

import CreateDelivery from '@/pages/Deliveries/CreateDelivery.vue';
import UpdateDelivery from '@/pages/Deliveries/UpdateDelivery.vue';
import DeleteDelivery from '@/pages/Deliveries/DeleteDelivery.vue';
import ViewDelivery from '@/pages/Deliveries/ViewDelivery.vue';

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Deliveries',
        href: '/deliveries',
    },
];

const props = defineProps({
    deliveries: {
        required: true,
    },

    columns: {
        type: Array,
        required: true,
    },

    suppliers: {
        type: Array,
        default: () => []
    },
});

const selectOptions = props.columns.filter(col => col.isParameter === true).map((s) => ({
    value: s.accessorKey,
    label: s.header,
}))
const selectModelValue = ref(
    selectOptions.length > 0 ? selectOptions[0].value : ''
);

const showCreateDeliveryModal = ref(false);
const showUpdateDeliveryModal = ref(false);
const showDeleteDeliveryModal = ref(false);
const showViewDeliveryModal = ref(false);
const selectedDelivery = ref(null);

const page = usePage();
const sortValue = ref(page.props.ziggy?.query?.sort ?? 'date_desc');

watch(sortValue, (val) => {
    router.get('/deliveries', { sort: val }, { preserveScroll: true, preserveState: true });
});

const handleAction = ({ type, data }) => {
    switch (type) {
        case 'view':
            selectedDelivery.value = data;
            showViewDeliveryModal.value = true;
            break;
        case 'edit':
            showUpdateDeliveryModal.value = true;
            selectedDelivery.value = data;
            break;
        case 'delete':
            showDeleteDeliveryModal.value = true;
            selectedDelivery.value = data;
            break;
        default:
            break;
    }
};

</script>

<template>

    <Head title="Deliveries" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="Deliveries" :data="props.deliveries"
                :columnDefs="columns.filter(col => col.isVisible === true)" :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue" @action="handleAction" :hover-fields="[
                    { field: 'supplier_name', label: 'Supplier' },
                    { field: 'delivery_date', label: 'Delivery Date' },
                    { field: 'status', label: 'Status' }
                ]">

                <Select v-model="sortValue">
                    <SelectTrigger class="w-44 mr-2">
                        <SelectValue placeholder="Sort by..." />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="date_desc">Date (Newest)</SelectItem>
                        <SelectItem value="date_asc">Date (Oldest)</SelectItem>
                        <SelectItem value="supplier">Supplier (A–Z)</SelectItem>
                    </SelectContent>
                </Select>

                <Button variant="default" class="mr-2" @click="showCreateDeliveryModal = true">
                    New Delivery
                </Button>

            </BaseIndex>

            <CreateDelivery v-if="showCreateDeliveryModal" @form-closed="showCreateDeliveryModal = false"
                :suppliers="suppliers" />

            <UpdateDelivery v-if="showUpdateDeliveryModal" :delivery="selectedDelivery" :suppliers="suppliers"
                @item-form-closed="showUpdateDeliveryModal = false" />

            <DeleteDelivery v-if="showDeleteDeliveryModal" :delivery="selectedDelivery"
                @item-form-closed="showDeleteDeliveryModal = false" />

            <ViewDelivery v-if="showViewDeliveryModal" :delivery="selectedDelivery"
                @form-closed="showViewDeliveryModal = false" />

        </div>
    </AppLayout>
</template>
