<script setup>
import { ref, onMounted, computed } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';
import FormCard from '@/components/FormCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import {
    Table, TableBody, TableCaption, TableCell,
    TableHead, TableHeader, TableRow,
} from '@/components/ui/table';

const props = defineProps({
    transaction: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isLoading = ref(true);
const items = ref([]);
const txInfo = ref(null);

onMounted(async () => {
    try {
        const res = await axios.get(`/pos/${props.transaction.id}`, {
            headers: { Accept: 'application/json' },
        });
        txInfo.value = res.data.transaction;
        items.value = res.data.items;
    } catch {
        toast.error('Failed to load transaction details.');
    } finally {
        isLoading.value = false;
    }
});

const formatAmount = (value) =>
    Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
    <FormCard :loading="isLoading" size="3xl">
        <div class="space-y-5 mt-4">

            <!-- Header info -->
            <div v-if="txInfo" class="rounded-lg border bg-muted/40 px-5 py-4">
                <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm">
                    <div>
                        <span class="text-muted-foreground">Receipt No.:</span>
                        <span class="ml-2 font-medium">{{ txInfo.receipt_no }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Date:</span>
                        <span class="ml-2 font-medium">{{ txInfo.sale_date }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Customer:</span>
                        <span class="ml-2 font-medium">{{ txInfo.customer_name }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Payment:</span>
                        <span class="ml-2 font-medium">{{ txInfo.payment_method }}</span>
                    </div>
                    <div v-if="txInfo.notes" class="col-span-2">
                        <span class="text-muted-foreground">Notes:</span>
                        <span class="ml-2 font-medium">{{ txInfo.notes }}</span>
                    </div>
                </div>
            </div>

            <!-- Items table -->
            <div class="rounded-md border overflow-auto">
                <Table>
                    <TableCaption>Transaction items</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Product</TableHead>
                            <TableHead class="text-center w-20">Qty</TableHead>
                            <TableHead class="text-center w-28">Unit Price</TableHead>
                            <TableHead class="text-center w-20">Disc %</TableHead>
                            <TableHead class="text-right w-28">Amount</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="items.length === 0">
                            <TableCell colspan="5" class="text-center text-muted-foreground py-4">No items.</TableCell>
                        </TableRow>
                        <TableRow v-for="item in items" :key="item.id">
                            <TableCell class="whitespace-normal break-words">{{ item.product_name }}</TableCell>
                            <TableCell class="text-center font-mono">{{ item.quantity }}</TableCell>
                            <TableCell class="text-center font-mono">{{ formatAmount(item.unit_price) }}</TableCell>
                            <TableCell class="text-center font-mono">{{ item.discount_percentage }}</TableCell>
                            <TableCell class="text-right font-mono font-medium">
                                {{ formatAmount(item.total_price) }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

        </div>

        <template #footer>
            <div class="mr-auto flex flex-col">
                <span class="text-xs text-muted-foreground uppercase tracking-wide">Total Amount</span>
                <span class="text-lg font-bold">{{ txInfo?.total_amount ?? '0.00' }}</span>
            </div>
            <BaseButton type="button" @click="emit('form-closed')" transactionType="cancel" />
        </template>
    </FormCard>
</template>
