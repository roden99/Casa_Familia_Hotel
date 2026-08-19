<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Field, FieldGroup, FieldLabel, FieldSeparator } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';
import { onMounted, ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { useFieldGroupSkeleton } from '@/composables/useFieldGroupSkeleton';
import {
    Table, TableBody, TableCell, TableHead,
    TableHeader, TableRow,
} from '@/components/ui/table';
import { Tag, PackageX } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['form-closed']);

const { skeletonLayout } = useFieldGroupSkeleton([10, 2, 4, 4]);
const { skeletonLayout: skeletonLayoutItems } = useFieldGroupSkeleton([12]);

const isLoading = ref(true);
const orderDetail = ref(null);
const items = ref([]);
const rgsRecords = ref([]);

const fmt = (value) =>
    Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const discountAmount = (item) => {
    const disc = Number(item.discount_percentage) || 0;
    return fmt(Number(item.quantity) * Number(item.unit_price) * (disc / 100));
};

const lineTotal = (item) => {
    const disc = Number(item.discount_percentage) || 0;
    return fmt(Number(item.quantity) * Number(item.unit_price) * (1 - disc / 100));
};

const grandTotal = computed(() =>
    items.value.reduce((sum, item) => {
        const disc = Number(item.discount_percentage) || 0;
        return sum + Number(item.quantity) * Number(item.unit_price) * (1 - disc / 100);
    }, 0)
);

onMounted(async () => {
    try {
        const res = await axios.get(`/sales-orders/${props.order.id}`, {
            headers: { Accept: 'application/json' },
        });
        orderDetail.value = res.data.order;
        items.value = res.data.items ?? [];
        rgsRecords.value = res.data.rgs ?? [];
    } catch {
        toast.error('Failed to load order details.');
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <FormCard :loading="isLoading" size="3xl">
        <div class="mt-4">
            <div class="grid grid-cols-12 gap-6 items-stretch">

                <!-- Left: Order Info (read-only) -->
                <div class="col-span-4">
                    <BaseField legend="Sales Order Information" description="View order details">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayout">
                                <div class="grid w-full grid-cols-12 gap-4">

                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal text-muted-foreground">Customer / Account
                                        </FieldLabel>
                                        <p class="text-sm font-semibold leading-tight mt-0.5">
                                            {{ order.customer_name ?? '—' }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">{{ order.account_name ?? '—' }}</p>
                                    </Field>

                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal text-muted-foreground">Terms (Days)</FieldLabel>
                                        <p class="text-sm font-semibold mt-0.5">{{ orderDetail?.terms ?? order.terms ??
                                            '—' }}</p>
                                    </Field>

                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal text-muted-foreground">Invoice No.</FieldLabel>
                                        <p class="text-sm font-semibold mt-0.5">{{ orderDetail?.invoice_no ??
                                            order.invoice_no ?? '—' }}</p>
                                    </Field>

                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal text-muted-foreground">Invoice Date</FieldLabel>
                                        <p class="text-sm font-semibold mt-0.5">{{ order.invoice_date ?? '—' }}</p>
                                    </Field>

                                </div>
                                <FieldSeparator />
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

                <!-- Right: Items table (read-only) -->
                <div class="col-span-8 flex flex-col">
                    <BaseField legend="Order Items" description="Items included in this order"
                        class="flex flex-col flex-1">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayoutItems"
                                class="flex flex-col flex-1">

                                <div class="overflow-y-auto rounded-md border flex-1 min-h-0">
                                    <Table class="text-xs">
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead class="text-xs">Item Name</TableHead>
                                                <TableHead class="text-xs w-24">Lot No.</TableHead>
                                                <TableHead class="text-xs text-center w-16">Qty</TableHead>
                                                <TableHead class="text-xs text-right w-24">UP</TableHead>
                                                <TableHead class="text-xs text-center w-20">Disc %</TableHead>
                                                <TableHead class="text-xs text-right w-24">Disc Amt</TableHead>
                                                <TableHead class="text-xs text-right w-28">Amount</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-if="items.length === 0">
                                                <TableCell colspan="7"
                                                    class="text-xs text-center text-muted-foreground py-4">
                                                    No items found.
                                                </TableCell>
                                            </TableRow>
                                            <TableRow v-for="(item, index) in items" :key="index">
                                                <TableCell class="text-xs whitespace-normal break-words min-w-0">
                                                    {{ item.product_name }}
                                                </TableCell>
                                                <TableCell class="text-xs">
                                                    <span v-if="item.lot_number"
                                                        class="inline-flex items-center gap-1 font-mono">
                                                        <Tag class="h-3 w-3 text-amber-500 shrink-0" />
                                                        {{ item.lot_number }}
                                                    </span>
                                                    <span v-else class="text-muted-foreground/40">—</span>
                                                </TableCell>
                                                <TableCell class="text-xs text-center">{{ item.quantity }}</TableCell>
                                                <TableCell class="text-xs text-right">{{ fmt(item.unit_price) }}
                                                </TableCell>
                                                <TableCell class="text-xs text-center">
                                                    {{ Number(item.discount_percentage) > 0 ? item.discount_percentage +
                                                        '%' : '—' }}
                                                </TableCell>
                                                <TableCell class="text-xs text-right">{{ discountAmount(item) }}
                                                </TableCell>
                                                <TableCell class="text-xs text-right font-medium">{{ lineTotal(item) }}
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>

                                <!-- Grand Total -->
                                <div class="flex justify-end pt-2 pr-1">
                                    <div class="flex items-center gap-3 rounded-md bg-muted/50 px-4 py-2 border">
                                        <span
                                            class="text-xs text-muted-foreground uppercase tracking-wide font-medium">Total</span>
                                        <span class="font-mono text-base font-bold">{{ fmt(grandTotal) }}</span>
                                    </div>
                                </div>

                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

            </div>

            <!-- RGS History -->
            <div v-if="rgsRecords.length > 0" class="mt-4">
                <div class="flex items-center gap-2 mb-2">
                    <PackageX class="h-4 w-4 text-orange-500" />
                    <span class="text-sm font-semibold text-orange-600 dark:text-orange-400">Return Good Stock
                        Records</span>
                    <span
                        class="ml-1 rounded-full bg-orange-100 dark:bg-orange-900/40 px-2 py-0.5 text-xs font-medium text-orange-700 dark:text-orange-300">{{
                        rgsRecords.length }}</span>
                </div>
                <div class="rounded-md border overflow-hidden">
                    <Table class="text-xs">
                        <TableHeader>
                            <TableRow>
                                <TableHead class="text-xs">RGS #</TableHead>
                                <TableHead class="text-xs">Date</TableHead>
                                <TableHead class="text-xs text-center w-20">Items</TableHead>
                                <TableHead class="text-xs">Notes</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="rgs in rgsRecords" :key="rgs.id"
                                class="hover:bg-orange-50 dark:hover:bg-orange-950/20">
                                <TableCell class="text-xs font-mono font-medium text-orange-600 dark:text-orange-400">
                                    #{{ rgs.id }}
                                </TableCell>
                                <TableCell class="text-xs">{{ rgs.rgs_date ?? '—' }}</TableCell>
                                <TableCell class="text-xs text-center">{{ rgs.items_count }}</TableCell>
                                <TableCell class="text-xs text-muted-foreground">{{ rgs.notes || '—' }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

        </div>

        <template #footer>
            <BaseButton type="button" @click="emit('form-closed')" transactionType="cancel" />
        </template>
    </FormCard>
</template>
