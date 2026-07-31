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
    carryItem: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isLoading = ref(true);
const items = ref([]);
const carryInfo = ref(null);

onMounted(async () => {
    try {
        const res = await axios.get(`/carry-items/${props.carryItem.id}`, {
            headers: { Accept: 'application/json' },
        });
        carryInfo.value = res.data.carryItem;
        items.value = res.data.items;
    } catch {
        toast.error('Failed to load carry item details.');
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <FormCard :loading="isLoading" size="3xl">
        <div class="space-y-4 mt-4">
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-muted-foreground">Sales Agent:</span>
                    <span class="ml-2 font-medium">{{ carryInfo?.sales_agent?.name ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-muted-foreground">Carry Date:</span>
                    <span class="ml-2 font-medium">{{ carryInfo?.carry_date }}</span>
                </div>
                <div>
                    <span class="text-muted-foreground">Notes:</span>
                    <span class="ml-2 font-medium">{{ carryInfo?.notes || '—' }}</span>
                </div>
            </div>

            <div class="rounded-md border overflow-auto">
                <Table>
                    <TableCaption>Carry item details</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Product</TableHead>
                            <TableHead class="text-center w-32">Quantity</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="items.length === 0">
                            <TableCell colspan="2" class="text-center text-muted-foreground py-4">No items.</TableCell>
                        </TableRow>
                        <TableRow v-for="item in items" :key="item.id">
                            <TableCell>{{ item.product_name }}</TableCell>
                            <TableCell class="text-center font-medium">{{ item.quantity }}</TableCell>
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
