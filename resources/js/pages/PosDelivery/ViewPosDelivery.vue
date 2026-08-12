<script setup>
import { ref, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';
import FormCard from '@/components/FormCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import {
    Table, TableBody, TableCaption, TableCell,
    TableHead, TableHeader, TableRow,
} from '@/components/ui/table';

const props = defineProps({
    delivery: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isLoading    = ref(true);
const deliveryInfo = ref(null);
const items        = ref([]);

const fmt = (val) =>
    val !== null && val !== undefined
        ? Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        : '—';

onMounted(async () => {
    try {
        const res = await axios.get(`/pos-deliveries/${props.delivery.id}`, {
            headers: { Accept: 'application/json' },
        });
        deliveryInfo.value = res.data.delivery;
        items.value        = res.data.items;
    } catch {
        toast.error('Failed to load delivery details.');
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <FormCard :loading="isLoading" size="3xl">
        <div class="space-y-4 mt-4">

            <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm rounded-md border bg-muted/40 px-4 py-3">
                <div>
                    <span class="text-muted-foreground">Supplier:</span>
                    <span class="ml-2 font-medium">{{ deliveryInfo?.supplier_name }}</span>
                </div>
                <div>
                    <span class="text-muted-foreground">Invoice No.:</span>
                    <span class="ml-2 font-medium">{{ deliveryInfo?.invoice_no }}</span>
                </div>
                <div>
                    <span class="text-muted-foreground">Delivery Date:</span>
                    <span class="ml-2 font-medium">{{ deliveryInfo?.delivery_date }}</span>
                </div>
                <div>
                    <span class="text-muted-foreground">Notes:</span>
                    <span class="ml-2 font-medium">{{ deliveryInfo?.notes || '—' }}</span>
                </div>
            </div>

            <div class="rounded-md border overflow-auto">
                <Table>
                    <TableCaption>Delivered items</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Product</TableHead>
                            <TableHead class="text-center w-28">Lot #</TableHead>
                            <TableHead class="text-center w-24">Expiry</TableHead>
                            <TableHead class="text-center w-20">Qty</TableHead>
                            <TableHead class="text-center w-24">Cost</TableHead>
                            <TableHead class="text-center w-24">Selling Price</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="items.length === 0">
                            <TableCell colspan="6" class="text-center text-muted-foreground py-4">No items.</TableCell>
                        </TableRow>
                        <TableRow v-for="item in items" :key="item.id">
                            <TableCell class="whitespace-normal break-words">{{ item.product_name }}</TableCell>
                            <TableCell class="text-center font-mono text-sm">{{ item.lot_number }}</TableCell>
                            <TableCell class="text-center text-sm">{{ item.expiration_date }}</TableCell>
                            <TableCell class="text-center font-mono">{{ item.quantity }}</TableCell>
                            <TableCell class="text-center font-mono">{{ fmt(item.cost) }}</TableCell>
                            <TableCell class="text-center font-mono">{{ fmt(item.selling_price) }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

        </div>

        <template #footer>
            <BaseButton type="button" @click="emit('form-closed')" transactionType="cancel" />
        </template>
    </FormCard>
</template>
