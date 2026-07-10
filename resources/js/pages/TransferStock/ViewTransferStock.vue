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
    transfer: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isLoading = ref(true);
const items = ref([]);
const transferInfo = ref(null);

onMounted(async () => {
    try {
        const res = await axios.get(`/transfer-stocks/${props.transfer.id}`, {
            headers: { Accept: 'application/json' },
        });
        transferInfo.value = res.data.transfer;
        items.value = res.data.items;
    } catch {
        toast.error('Failed to load transfer details.');
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <FormCard :loading="isLoading" size="3xl">
        <div class="space-y-4 mt-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-muted-foreground">Transfer Date:</span>
                    <span class="ml-2 font-medium">{{ transferInfo?.transfer_date }}</span>
                </div>
                <div>
                    <span class="text-muted-foreground">Notes:</span>
                    <span class="ml-2 font-medium">{{ transferInfo?.notes || '—' }}</span>
                </div>
            </div>

            <div class="rounded-md border overflow-auto">
                <Table>
                    <TableCaption>Transfer items</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Product</TableHead>
                            <TableHead class="text-center w-24">Qty</TableHead>
                            <TableHead class="text-center w-24">Multiplier</TableHead>
                            <TableHead class="text-center w-28">POS Qty Added</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="items.length === 0">
                            <TableCell colspan="4" class="text-center text-muted-foreground py-4">No items.</TableCell>
                        </TableRow>
                        <TableRow v-for="item in items" :key="item.id">
                            <TableCell>{{ item.product_name }}</TableCell>
                            <TableCell class="text-center">{{ item.quantity }}</TableCell>
                            <TableCell class="text-center">{{ item.multiplier }}</TableCell>
                            <TableCell class="text-center font-medium text-teal-600">
                                {{ item.pos_qty_added }}
                            </TableCell>
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
