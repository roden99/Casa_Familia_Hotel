<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ref, computed, h } from 'vue';
import { router, Head } from '@inertiajs/vue3';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Payments', href: '/payments' },
];

const props = defineProps({
    payments: { required: true },
    columns: { type: Array, required: true },
    paymentMethods: { type: Array, default: () => [] },
});

const params = new URLSearchParams(window.location.search);
const currentMethod = ref(params.get('payment_method') || 'all');

const selectOptions = props.columns
    .filter(col => col.isParameter === true)
    .map(s => ({ value: s.accessorKey, label: s.header }));

const selectModelValue = ref(selectOptions.length > 0 ? selectOptions[0].value : '');

const applyFilter = (key, value) => {
    const url = new URL(window.location.href);
    url.searchParams.delete('page');
    if (value && value !== 'all') url.searchParams.set(key, value);
    else url.searchParams.delete(key);
    router.get(url.pathname + url.search, {}, { preserveScroll: true });
};

const handleMethodFilter = (value) => {
    currentMethod.value = value;
    applyFilter('payment_method', value);
};

const enrichedColumns = computed(() =>
    props.columns.map(col => {
        if (col.accessorKey === 'tag_status') {
            return {
                ...col,
                cell: ({ row }) => {
                    const status = row.original.tag_status;
                    const cls = status === 'Untagged'
                        ? 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
                        : 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300';
                    return h('span', { class: cls }, status);
                },
            };
        }
        if (col.accessorKey === 'amount') {
            return {
                ...col,
                cell: ({ row }) =>
                    h('span', { class: 'font-mono font-semibold text-green-600 dark:text-green-400' }, row.original.amount),
            };
        }
        return col;
    })
);
</script>

<template>
    <Head title="Payments" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">

            <BaseIndex
                IndexType="Payments"
                :data="payments"
                :columnDefs="enrichedColumns.filter(col => col.isVisible === true)"
                :selectOptions="selectOptions"
                v-model:selectModelValue="selectModelValue"
                :hover-fields="[
                    { field: 'check_number', label: 'Check No.' },
                    { field: 'check_date', label: 'Check Date' },
                    { field: 'notes', label: 'Notes' },
                ]"
            >
                <Select :model-value="currentMethod" @update:model-value="handleMethodFilter">
                    <SelectTrigger class="w-44">
                        <SelectValue placeholder="All Methods" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Methods</SelectItem>
                        <SelectItem v-for="method in paymentMethods" :key="method" :value="method">
                            {{ method }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </BaseIndex>

        </div>
    </AppLayout>
</template>
