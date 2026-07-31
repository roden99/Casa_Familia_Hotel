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
</script>

<template>
    <div class="overflow-y-auto rounded-md border flex-1 min-h-0">
        <Table>
            <TableCaption>List of carry items</TableCaption>
            <TableHeader>
                <TableRow>
                    <TableHead>Product Name</TableHead>
                    <TableHead class="w-28">Lot No.</TableHead>
                    <TableHead class="text-center w-32">Quantity</TableHead>
                    <TableHead class="w-8" />
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-if="items.length === 0">
                    <TableCell colspan="4" class="text-center text-muted-foreground py-4">
                        No items added yet.
                    </TableCell>
                </TableRow>
                <TableRow v-for="(item, index) in items" :key="index">
                    <TableCell class="whitespace-normal break-words min-w-0">{{ item.product_name }}</TableCell>
                    <TableCell class="text-muted-foreground text-sm">{{ item.lot_number ?? '—' }}</TableCell>
                    <TableCell class="text-center">
                        <Input v-model.number="item.quantity" type="number" min="0.0001" step="0.0001"
                            class="w-24 text-center mx-auto" />
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
