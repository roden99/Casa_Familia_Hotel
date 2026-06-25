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
import axios from 'axios';

const props = defineProps({
    delivery: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['form-closed']);

const { skeletonLayout } = useFieldGroupSkeleton([10, 2, 4, 4, 4]);
const { skeletonLayout: skeletonLayoutItems } = useFieldGroupSkeleton([12]);

const isLoading = ref(true);
const deliveryDetail = ref(null);
const items = ref([]);

const fmt = (value) =>
    Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const lineTotal = (item) =>
    fmt(Number(item.quantity) * Number(item.unit_price));

const grandTotal = computed(() =>
    items.value.reduce((sum, item) => sum + Number(item.quantity) * Number(item.unit_price), 0)
);

onMounted(async () => {
    try {
        const res = await axios.get(`/deliveries/${props.delivery.id}`, {
            headers: { Accept: 'application/json' },
        });
        deliveryDetail.value = res.data.delivery;
        items.value = res.data.items ?? [];
    } catch {
        toast.error('Failed to load delivery details.');
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <FormCard :loading="isLoading" size="3xl">
        <div class="mt-4">
            <div class="grid grid-cols-12 gap-6 items-stretch">

                <!-- Left: Delivery Info (read-only) -->
                <div class="col-span-4">
                    <BaseField legend="Delivery Information" description="View delivery details">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayout">
                                <div class="grid w-full grid-cols-12 gap-4">

                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal text-muted-foreground">Supplier</FieldLabel>
                                        <p class="text-sm font-semibold mt-0.5">{{ delivery.supplier_name ?? '—' }}</p>
                                    </Field>

                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal text-muted-foreground">Invoice No.</FieldLabel>
                                        <p class="text-sm font-semibold mt-0.5">{{ delivery.invoice_no ?? '—' }}</p>
                                    </Field>

                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal text-muted-foreground">Invoice Date</FieldLabel>
                                        <p class="text-sm font-semibold mt-0.5">{{ delivery.invoice_date ?? '—' }}</p>
                                    </Field>

                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal text-muted-foreground">Delivery Date</FieldLabel>
                                        <p class="text-sm font-semibold mt-0.5">{{ delivery.delivery_date ?? '—' }}</p>
                                    </Field>

                                </div>
                                <FieldSeparator />
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

                <!-- Right: Items table (read-only) -->
                <div class="col-span-8 flex flex-col">
                    <BaseField legend="Delivery Items" description="Items received in this delivery"
                        class="flex flex-col flex-1">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayoutItems"
                                class="flex flex-col flex-1">

                                <div class="overflow-y-auto rounded-md border flex-1 min-h-0">
                                    <Table class="text-xs">
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead class="text-xs">Item Name</TableHead>
                                                <TableHead class="text-xs text-center w-16">Qty</TableHead>
                                                <TableHead class="text-xs text-right w-28">Unit Price</TableHead>
                                                <TableHead class="text-xs text-right w-28">Amount</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-if="items.length === 0">
                                                <TableCell colspan="4"
                                                    class="text-xs text-center text-muted-foreground py-4">
                                                    No items found.
                                                </TableCell>
                                            </TableRow>
                                            <TableRow v-for="(item, index) in items" :key="index">
                                                <TableCell class="text-xs whitespace-normal break-words min-w-0">
                                                    {{ item.product_name }}
                                                </TableCell>
                                                <TableCell class="text-xs text-center">{{ item.quantity }}</TableCell>
                                                <TableCell class="text-xs text-right">{{ fmt(item.unit_price) }}
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
        </div>

        <template #footer>
            <BaseButton type="button" @click="emit('form-closed')" transactionType="cancel" />
        </template>
    </FormCard>
</template>
