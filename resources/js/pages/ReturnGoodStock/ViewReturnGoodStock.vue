<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Field, FieldGroup, FieldLabel, FieldSeparator } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';
import {
    Table, TableBody, TableCell, TableHead,
    TableHeader, TableRow,
} from '@/components/ui/table';
import { Tag } from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';

const props = defineProps({
    record: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isLoading = ref(true);
const rgsInfo = ref(null);
const items = ref([]);

const fmt = (v) =>
    Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const grandTotal = computed(() =>
    items.value.reduce((s, i) => s + Number(i.quantity) * Number(i.unit_price), 0)
);

onMounted(async () => {
    try {
        const res = await axios.get(`/return-good-stocks/${props.record.id}`, {
            headers: { Accept: 'application/json' },
        });
        rgsInfo.value = res.data.rgs;
        items.value = res.data.items ?? [];
    } catch {
        toast.error('Failed to load RGS details.');
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <FormCard :loading="isLoading" size="3xl" cardTitle="View Return Good Stock">
        <div class="mt-4">
            <div class="grid grid-cols-12 gap-6">

                <!-- Left: header info -->
                <div class="col-span-4">
                    <BaseField legend="RGS Information" description="Return details">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading">
                                <div class="grid w-full grid-cols-12 gap-4">
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal text-muted-foreground">Customer / Account
                                        </FieldLabel>
                                        <p class="text-sm font-semibold leading-tight mt-0.5">{{ rgsInfo?.customer_name
                                            ?? '—' }}</p>
                                        <p class="text-xs text-muted-foreground">{{ rgsInfo?.account_name ?? '—' }}</p>
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal text-muted-foreground">Reference Invoice No.
                                        </FieldLabel>
                                        <p class="text-sm font-semibold mt-0.5">{{ rgsInfo?.invoice_no ?? '—' }}</p>
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal text-muted-foreground">RGS Date</FieldLabel>
                                        <p class="text-sm font-semibold mt-0.5">{{ rgsInfo?.rgs_date ?? '—' }}</p>
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal text-muted-foreground">Notes</FieldLabel>
                                        <p class="text-sm mt-0.5">{{ rgsInfo?.notes || '—' }}</p>
                                    </Field>
                                </div>
                                <FieldSeparator />
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

                <!-- Right: items table -->
                <div class="col-span-8 flex flex-col">
                    <BaseField legend="Returned Items" description="Items included in this return"
                        class="flex flex-col flex-1">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" class="flex flex-col flex-1">
                                <div class="overflow-y-auto rounded-md border flex-1 min-h-0">
                                    <Table class="text-xs">
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead class="text-xs">Item Name</TableHead>
                                                <TableHead class="text-xs w-28">Lot No.</TableHead>
                                                <TableHead class="text-xs text-center w-20">Qty</TableHead>
                                                <TableHead class="text-xs text-right w-24">Unit Price</TableHead>
                                                <TableHead class="text-xs text-right w-28">Amount</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-if="items.length === 0 && !isLoading">
                                                <TableCell colspan="5"
                                                    class="text-xs text-center text-muted-foreground py-4">No items.
                                                </TableCell>
                                            </TableRow>
                                            <TableRow v-for="item in items" :key="item.id">
                                                <TableCell class="text-xs whitespace-normal break-words">{{
                                                    item.product_name }}</TableCell>
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
                                                <TableCell class="text-xs text-right font-medium">{{
                                                    fmt(Number(item.quantity) * Number(item.unit_price)) }}</TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
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
