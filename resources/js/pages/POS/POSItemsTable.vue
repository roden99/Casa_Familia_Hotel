<script setup>
import { X } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['remove']);

const formatAmount = (value) =>
    Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const computeTotal = (item) => {
    const disc = Number(item.discount_percentage) || 0;
    return formatAmount(item.quantity * Number(item.unit_price) * (1 - disc / 100));
};
</script>

<template>
    <div class="overflow-y-auto rounded-md border flex-1 min-h-0">
        <Table class="text-xs">
            <TableCaption class="text-xs">List of items</TableCaption>
            <TableHeader>
                <TableRow>
                    <TableHead class="text-xs">Item Name</TableHead>
                    <TableHead class="text-xs text-center w-20">POS Qty</TableHead>
                    <TableHead class="text-xs text-center w-28">Lot #</TableHead>
                    <TableHead class="text-xs text-center w-24">Expiry</TableHead>
                    <TableHead class="text-xs text-center w-20">Qty</TableHead>
                    <TableHead class="text-xs text-center w-28">Unit Price</TableHead>
                    <TableHead class="text-xs text-center w-20">Disc %</TableHead>
                    <TableHead class="text-xs text-right w-28">Amount</TableHead>
                    <TableHead class="w-8" />
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-if="items.length === 0">
                    <TableCell colspan="9" class="text-xs text-center text-muted-foreground py-4">
                        No items added yet.
                    </TableCell>
                </TableRow>
                <TableRow v-for="(item, index) in items" :key="index">
                    <TableCell class="text-xs whitespace-normal wrap-break-word min-w-0">
                        {{ item.product_name }}
                    </TableCell>
                    <TableCell class="text-center text-xs text-muted-foreground">
                        {{ item.pos_qty }}
                    </TableCell>
                    <TableCell class="text-center text-xs font-mono">
                        {{ item.lot_number ?? '—' }}
                    </TableCell>
                    <TableCell class="text-center text-xs">
                        {{ item.expiration_date ?? '—' }}
                    </TableCell>
                    <TableCell class="text-center">
                        <Input v-model.number="item.quantity" type="number" min="1" step="1"
                            class="w-20 text-xs text-center mx-auto" />
                    </TableCell>
                    <TableCell class="text-center">
                        <Input v-model.number="item.unit_price" type="number" min="0" step="0.01"
                            class="w-24 text-xs text-center mx-auto" />
                    </TableCell>
                    <TableCell class="text-center">
                        <Input v-model.number="item.discount_percentage" type="number" min="0" max="100" step="0.01"
                            class="w-20 text-xs text-center mx-auto" />
                    </TableCell>
                    <TableCell class="text-xs text-right font-medium">{{ computeTotal(item) }}</TableCell>
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
