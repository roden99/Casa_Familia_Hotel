<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';
import { useFieldGroupSkeleton } from '@/composables/useFieldGroupSkeleton';
import {
    Table, TableBody, TableCell, TableHead,
    TableHeader, TableRow,
} from '@/components/ui/table';
import { Tag } from 'lucide-vue-next';
import { ref, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';

const props = defineProps({
    record: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const { skeletonLayout } = useFieldGroupSkeleton([10, 2]);
const { skeletonLayout: skeletonLayoutItems } = useFieldGroupSkeleton([12]);

const isLoading = ref(true);
const rtsInfo = ref(null);
const items = ref([]);

onMounted(async () => {
    try {
        const res = await axios.get(`/return-to-suppliers/${props.record.id}`, {
            headers: { Accept: 'application/json' },
        });
        rtsInfo.value = res.data.rts;
        items.value = res.data.items ?? [];
    } catch {
        toast.error('Failed to load return details.');
        emit('form-closed');
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <FormCard :loading="isLoading" size="3xl" cardTitle="View Return to Supplier">
        <div class="mt-4">
            <div class="grid grid-cols-12 gap-6 items-stretch">

                <!-- Left: header info -->
                <div class="col-span-4">
                    <BaseField legend="Return Information" description="Return to supplier details">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayout">
                                <div class="grid w-full grid-cols-12 gap-4">
                                    <Field class="col-span-12">
                                        <FieldLabel>Supplier</FieldLabel>
                                        <p class="text-sm font-semibold mt-0.5">{{ rtsInfo?.supplier_name ?? '—' }}</p>
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel>Return Date</FieldLabel>
                                        <p class="text-sm font-semibold mt-0.5">{{ rtsInfo?.return_date ?? '—' }}</p>
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel>Notes</FieldLabel>
                                        <p class="text-sm text-muted-foreground mt-0.5">
                                            {{ rtsInfo?.notes || 'No notes.' }}
                                        </p>
                                    </Field>
                                </div>
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

                <!-- Right: items table -->
                <div class="col-span-8 flex flex-col">
                    <BaseField legend="Returned Items" description="Items returned to supplier"
                        class="flex flex-col flex-1">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayoutItems"
                                class="flex flex-col flex-1">
                                <div class="overflow-y-auto rounded-md border flex-1 min-h-0 max-h-80">
                                    <Table class="text-xs">
                                        <TableHeader class="sticky top-0 z-10">
                                            <TableRow>
                                                <TableHead class="text-xs">Item Name</TableHead>
                                                <TableHead class="text-xs w-28">Lot No.</TableHead>
                                                <TableHead class="text-xs w-24">Expiry</TableHead>
                                                <TableHead class="text-xs text-center w-20">Qty</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-if="items.length === 0 && !isLoading">
                                                <TableCell colspan="4"
                                                    class="text-xs text-center text-muted-foreground py-4">
                                                    No items.
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
                                                <TableCell class="text-xs text-muted-foreground">
                                                    {{ item.expiration_date ?? '—' }}
                                                </TableCell>
                                                <TableCell class="text-xs text-center font-medium">
                                                    {{ item.quantity }}
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
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
