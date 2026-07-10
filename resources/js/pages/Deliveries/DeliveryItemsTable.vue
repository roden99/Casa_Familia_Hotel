<script setup>
import { X } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Tag } from 'lucide-vue-next';
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
</script>

<template>
    <div class="overflow-y-auto rounded-md border flex-1 min-h-0">
        <Table>
            <TableCaption>List of delivery items</TableCaption>
            <TableHeader>
                <TableRow>
                    <TableHead>Item Name</TableHead>
                    <TableHead class="text-center w-20">Qty</TableHead>
                    <TableHead class="text-center w-24">UP</TableHead>
                    <TableHead class="text-center w-24">Amount</TableHead>
                    <TableHead class="w-28">Lot No.</TableHead>
                    <TableHead class="w-28">Expiry</TableHead>
                    <TableHead class="w-8" />
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-if="items.length === 0">
                    <TableCell colspan="7" class="text-center text-muted-foreground py-4">
                        No items added yet.
                    </TableCell>
                </TableRow>
                <TableRow v-for="(item, index) in items" :key="index">
                    <TableCell class="whitespace-normal break-words min-w-0 text-sm">{{ item.product_name }}</TableCell>
                    <TableCell class="text-center">
                        <Input v-model.number="item.quantity" type="number" min="1" class="w-16 text-center mx-auto" />
                    </TableCell>
                    <TableCell class="text-center">
                        <Input v-model.number="item.unit_price" type="number" min="0" step="0.01"
                            class="w-20 text-center mx-auto" />
                    </TableCell>
                    <TableCell class="text-right text-sm font-mono">
                        {{ (item.quantity * Number(item.unit_price)).toFixed(2) }}
                    </TableCell>
                    <TableCell class="text-xs font-mono text-muted-foreground">
                        <span v-if="item.lot_number" class="inline-flex items-center gap-1">
                            <Tag class="h-3 w-3 text-amber-500" />
                            {{ item.lot_number }}
                        </span>
                        <span v-else class="text-muted-foreground/40">—</span>
                    </TableCell>
                    <TableCell class="text-xs text-muted-foreground">
                        {{ item.expiration_date || '—' }}
                    </TableCell>
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
