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
</script>

<template>
    <div class="overflow-y-auto rounded-md border flex-1 min-h-0">
        <Table>
            <TableCaption>List of items to transfer</TableCaption>
            <TableHeader>
                <TableRow>
                    <TableHead>Product Name</TableHead>
                    <TableHead class="text-center w-28">Lot #</TableHead>
                    <TableHead class="text-center w-24">Qty</TableHead>
                    <TableHead class="text-center w-24">Multiplier</TableHead>
                    <TableHead class="text-center w-28">POS Qty Added</TableHead>
                    <TableHead class="w-8" />
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-if="items.length === 0">
                    <TableCell colspan="6" class="text-center text-muted-foreground py-4">
                        No items added yet.
                    </TableCell>
                </TableRow>
                <TableRow v-for="(item, index) in items" :key="index">
                    <TableCell class="whitespace-normal break-words min-w-0">{{ item.product_name }}</TableCell>
                    <TableCell class="text-center font-mono text-xs">{{ item.lot_number ?? '—' }}</TableCell>
                    <TableCell class="text-center">
                        <Input v-model.number="item.quantity" type="number" min="1" step="1"
                            class="w-20 text-center mx-auto" />
                    </TableCell>
                    <TableCell class="text-center">
                        <span class="text-sm font-medium text-muted-foreground">{{ item.multiplier }}</span>
                    </TableCell>
                    <TableCell class="text-center font-medium text-teal-600">
                        {{ Math.floor(item.quantity * item.multiplier) }}
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
