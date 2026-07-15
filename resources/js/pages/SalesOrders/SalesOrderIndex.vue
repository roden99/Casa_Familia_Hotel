<script setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { ref, computed, h } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

import CreateSalesOrder from '@/pages/SalesOrders/CreateSalesOrder.vue';
import UpdateSalesOrder from '@/pages/SalesOrders/UpdateSalesOrder.vue';
import ViewSalesOrder from '@/pages/SalesOrders/ViewSalesOrder.vue';
import DeleteSalesOrder from '@/pages/SalesOrders/DeleteSalesOrder.vue';
import ViewSalesOrderPayment from '@/pages/SalesOrders/ViewSalesOrderPayment.vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Sales Orders', href: '/sales-orders' },
];

const props = defineProps({
    orders: {
        required: true,
    },
    columns: {
        type: Array,
        required: true,
    },
    filter: {
        type: String,
        default: null,
    },
});

const selectOptions = props.columns
    .filter(col => col.isParameter === true)
    .map(s => ({ value: s.accessorKey, label: s.header }));

const transformedColumns = computed(() =>
    props.columns
        .filter(col => col.isVisible === true)
        .map(col => {
            if (col.accessorKey === 'entry_type') {
                return {
                    ...col,
                    cell: ({ row }) => {
                        const isSO = row.original.entry_type === 'SO';
                        return h('span', {
                            class: isSO
                                ? 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
                                : 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                        }, isSO ? 'SO' : 'INV');
                    },
                };
            }
            if (col.accessorKey === 'payment_status') {
                return {
                    ...col,
                    cell: ({ row }) => {
                        const status = row.original.payment_status;
                        const cls = status === 'Paid'
                            ? 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                            : status === 'Partial'
                                ? 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
                                : 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400';
                        return h('span', { class: cls }, status ?? 'Unpaid');
                    },
                };
            }
            return col;
        })
);

const selectModelValue = ref(
    selectOptions.length > 0 ? selectOptions[0].value : ''
);

const showCreateModal = ref(false);
const showUpdateModal = ref(false);
const showViewModal = ref(false);
const showDeleteModal = ref(false);
const showPaymentModal = ref(false);
const selectedOrder = ref(null);

// ─── Due date helpers ─────────────────────────────────────────────────────────
// due_date format from server: "m-d-Y" e.g. "07-15-2026"
const parseDueDate = (dateStr) => {
    if (!dateStr) return null;
    const [month, day, year] = dateStr.split('-');
    return new Date(Number(year), Number(month) - 1, Number(day));
};

const today = new Date();
today.setHours(0, 0, 0, 0);
const oneWeekLater = new Date(today);
oneWeekLater.setDate(today.getDate() + 7);

const getDueStatus = (row) => {
    const due = parseDueDate(row.due_date);
    if (!due) return null;
    if (due < today) return 'overdue';
    if (due <= oneWeekLater) return 'upcoming';
    return null;
};

// ─── Active filter (server-side) ─────────────────────────────────────────────
const activeFilter = ref(props.filter ?? null);

const toggleFilter = (filter) => {
    const next = activeFilter.value === filter ? null : filter;
    activeFilter.value = next;
    const url = new URL(window.location.href);
    url.searchParams.delete('page');
    if (next) {
        url.searchParams.set('filter', next);
    } else {
        url.searchParams.delete('filter');
    }
    router.get(url.pathname + url.search, {}, { preserveScroll: true });
};

// ─── Row class ────────────────────────────────────────────────────────────────
const rowClass = (row) => {
    if (row.payment_status === 'Paid') return ''; const status = getDueStatus(row);
    if (status === 'overdue') return 'bg-red-200 dark:bg-red-900';
    if (status === 'upcoming') return 'bg-yellow-100 dark:bg-yellow-950';
    return '';
};

const handleAction = ({ type, data }) => {
    switch (type) {
        case 'payment_details':
            selectedOrder.value = data;
            showPaymentModal.value = true;
            break;
        case 'view':
            if (data.entry_type === 'INV') return;
            selectedOrder.value = data;
            showViewModal.value = true;
            break;
        case 'edit':
            if (data.entry_type === 'INV') return;
            selectedOrder.value = data;
            showUpdateModal.value = true;
            break;
        case 'delete':
            if (data.payment_status === 'Paid') {
                toast.error('Cannot delete a paid sales order.');
                return;
            }
            selectedOrder.value = data;
            showDeleteModal.value = true;
            break;
        default:
            break;
    }
};
</script>

<template>

    <Head title="Sales Orders" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="Sales Orders" :data="orders" :columnDefs="transformedColumns"
                :selectOptions="selectOptions" v-model:selectModelValue="selectModelValue" @action="handleAction"
                :row-class="rowClass" :hover-fields="[
                    { field: 'account_name', label: 'Account' },
                    { field: 'customer_name', label: 'Customer' },
                ]">

                <Button variant="default" class="mr-2" @click="showCreateModal = true">
                    New Sales Order
                </Button>

                <div class="flex items-center gap-1 ml-2 border rounded-md p-1">
                    <Button :variant="activeFilter === null ? 'default' : 'ghost'" size="sm"
                        @click="activeFilter = null">
                        All
                    </Button>
                    <Button :variant="activeFilter === 'overdue' ? 'destructive' : 'ghost'" size="sm"
                        :class="activeFilter !== 'overdue' && 'text-red-600 hover:text-red-700 dark:text-red-400'"
                        @click="toggleFilter('overdue')">
                        Due Sales
                    </Button>
                    <Button :variant="activeFilter === 'upcoming' ? 'default' : 'ghost'" size="sm" :class="activeFilter === 'upcoming'
                        ? 'bg-yellow-400 text-yellow-900 hover:bg-yellow-500'
                        : 'text-yellow-600 hover:text-yellow-700 dark:text-yellow-400'"
                        @click="toggleFilter('upcoming')">
                        For Collection
                    </Button>
                    <Button :variant="activeFilter === 'paid' ? 'default' : 'ghost'" size="sm" :class="activeFilter === 'paid'
                        ? 'bg-green-600 text-white hover:bg-green-700'
                        : 'text-green-600 hover:text-green-700 dark:text-green-400'" @click="toggleFilter('paid')">
                        Paid
                    </Button>
                    <Button :variant="activeFilter === 'unpaid' ? 'default' : 'ghost'" size="sm" :class="activeFilter === 'unpaid'
                        ? 'bg-gray-600 text-white hover:bg-gray-700'
                        : 'text-gray-500 hover:text-gray-600 dark:text-gray-400'" @click="toggleFilter('unpaid')">
                        Unpaid
                    </Button>
                </div>

            </BaseIndex>

            <CreateSalesOrder v-if="showCreateModal" @form-closed="showCreateModal = false" />

            <UpdateSalesOrder v-if="showUpdateModal" :order="selectedOrder"
                @item-form-closed="showUpdateModal = false" />

            <ViewSalesOrder v-if="showViewModal" :order="selectedOrder" @form-closed="showViewModal = false" />

            <DeleteSalesOrder v-if="showDeleteModal" :order="selectedOrder"
                @item-form-closed="showDeleteModal = false" />

            <ViewSalesOrderPayment v-if="showPaymentModal" :order="selectedOrder"
                @form-closed="showPaymentModal = false" />
        </div>
    </AppLayout>
</template>
