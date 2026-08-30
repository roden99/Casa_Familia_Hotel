<script setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BaseIndex from '@/components/BaseIndex.vue';
import { ref, computed, h } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { AlertTriangle, Tag } from 'lucide-vue-next';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Expirations', href: '/expirations' },
];

const props = defineProps({
    lots: { required: true },
    columns: { type: Array, required: true },
    filter: { type: String, default: null },
});

const selectOptions = props.columns
    .filter(col => col.isParameter === true)
    .map(s => ({ value: s.accessorKey, label: s.header }));

const selectModelValue = ref(selectOptions.length > 0 ? selectOptions[0].value : '');

const transformedColumns = computed(() =>
    props.columns
        .filter(col => col.isVisible === true)
        .map(col => {
            if (col.accessorKey === 'lot_number') {
                return {
                    ...col,
                    cell: ({ row }) => h('span', { class: 'inline-flex items-center gap-1 font-mono text-xs' }, [
                        h(Tag, { class: 'h-3 w-3 text-amber-500 shrink-0' }),
                        row.original.lot_number,
                    ]),
                };
            }
            if (col.accessorKey === 'days_until_expiry') {
                return {
                    ...col,
                    cell: ({ row }) => {
                        const days = row.original.days_until_expiry;
                        if (days < 0) {
                            return h('span', { class: 'inline-flex items-center gap-1 text-red-600 font-semibold text-xs' }, [
                                h(AlertTriangle, { class: 'h-3 w-3 shrink-0' }),
                                `${Math.abs(days)}d ago`,
                            ]);
                        }
                        const cls = days <= 30
                            ? 'text-red-600 font-semibold'
                            : days <= 90
                                ? 'text-amber-600 font-semibold'
                                : 'text-muted-foreground';
                        return h('span', { class: `text-xs ${cls}` }, `${days}d`);
                    },
                };
            }
            if (col.accessorKey === 'status') {
                return {
                    ...col,
                    cell: ({ row }) => {
                        const s = row.original.status;
                        const cls = s === 'Expired'
                            ? 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
                            : s === 'Expiring Soon'
                                ? 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
                                : 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300';
                        return h('span', { class: cls }, s);
                    },
                };
            }
            if (col.accessorKey === 'quantity') {
                return {
                    ...col,
                    cell: ({ row }) => h('span', { class: 'text-xs font-mono' },
                        Number(row.original.quantity).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
                    ),
                };
            }
            return col;
        })
);

// ─── Server-side filter ───────────────────────────────────────────────────────
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

// ─── Row highlight ────────────────────────────────────────────────────────────
const rowClass = (row) => {
    if (row.status === 'Expired') return 'bg-red-100 dark:bg-red-900/40';
    if (row.status === 'Expiring Soon') return 'bg-amber-100 dark:bg-amber-900/40';
    return '';
};
</script>

<template>

    <Head title="Expirations" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <BaseIndex IndexType="Expirations" :data="lots" :columnDefs="transformedColumns"
                :selectOptions="selectOptions" v-model:selectModelValue="selectModelValue" :custom-actions="[]"
                :row-class="rowClass">
                <!-- Filter buttons -->
                <div class="flex items-center gap-1 border rounded-md p-1">
                    <Button :variant="activeFilter === null ? 'default' : 'ghost'" size="sm"
                        @click="toggleFilter(null)">
                        All
                    </Button>
                    <Button :variant="activeFilter === 'expired' ? 'destructive' : 'ghost'" size="sm"
                        :class="activeFilter !== 'expired' && 'text-red-600 hover:text-red-700 dark:text-red-400'"
                        @click="toggleFilter('expired')">
                        <AlertTriangle class="h-3.5 w-3.5 mr-1" />
                        Expired
                    </Button>
                    <Button :variant="activeFilter === 'soon' ? 'default' : 'ghost'" size="sm" :class="activeFilter === 'soon'
                        ? 'bg-amber-400 text-amber-900 hover:bg-amber-500'
                        : 'text-amber-600 hover:text-amber-700 dark:text-amber-400'" @click="toggleFilter('soon')">
                        Expiring Soon
                    </Button>
                    <Button :variant="activeFilter === 'ok' ? 'default' : 'ghost'" size="sm" :class="activeFilter === 'ok'
                        ? 'bg-green-600 text-white hover:bg-green-700'
                        : 'text-green-600 hover:text-green-700 dark:text-green-400'" @click="toggleFilter('ok')">
                        Good
                    </Button>
                </div>
            </BaseIndex>
        </div>
    </AppLayout>
</template>
