<script setup>
import { X } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import {
    Table, TableBody, TableCaption, TableCell,
    TableHead, TableHeader, TableRow,
} from '@/components/ui/table';

const props = defineProps({
    items: { type: Array, default: () => [] },
});

const emit = defineEmits(['remove']);

const fmt = (val) =>
    val !== null && val !== undefined
        ? Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        : '—';
</script>

<template>
    <div class="overflow-y-auto rounded-md border flex-1 min-h-0">
        <Table class="text-xs">
            <TableCaption class="text-xs">Items to deliver to POS store</TableCaption>
            <TableHeader>
                <TableRow>
                    <TableHead class="text-xs">Product</TableHead>
                    <TableHead class="text-xs text-center w-28">Lot #</TableHead>
                    <TableHead class="text-xs text-center w-24">Expiry</TableHead>
                    <TableHead class="text-xs text-center w-20">Qty</TableHead>
                    <TableHead class="text-xs text-center w-24">Cost</TableHead>
                    <TableHead class="text-xs text-center w-24">Selling Price</TableHead>
                    <TableHead class="w-8" />
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-if="items.length === 0">
                    <TableCell colspan="7" class="text-xs text-center text-muted-foreground py-4">
                        No items added yet.
                    </TableCell>
                </TableRow>
                <TableRow v-for="(item, index) in items" :key="index">
                    <TableCell class="text-xs whitespace-normal break-words min-w-0">{{ item.product_name }}</TableCell>
                    <TableCell class="text-center text-xs font-mono">{{ item.lot_number }}</TableCell>
                    <TableCell class="text-center text-xs">{{ item.expiration_date ?? '—' }}</TableCell>
                    <TableCell class="text-center">
                        <Input v-model.number="item.quantity" type="number" min="0.0001" step="0.0001"
                            class="w-16 text-xs text-center mx-auto" />
                    </TableCell>
                    <TableCell class="text-center text-xs font-mono">{{ fmt(item.cost) }}</TableCell>
                    <TableCell class="text-center text-xs font-mono">{{ fmt(item.selling_price) }}</TableCell>
                    <TableCell class="text-center">
                        <button type="button" @click="emit('remove', index)" class="text-destructive hover:opacity-70">
                            <X class="h-4 w-4" />
                        </button>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
