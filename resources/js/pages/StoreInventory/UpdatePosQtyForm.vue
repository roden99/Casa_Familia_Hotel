<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { FieldLabel } from '@/components/ui/field';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import { X } from 'lucide-vue-next';
import {
    Table, TableBody, TableCell,
    TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import axios from 'axios';

const props = defineProps({
    isProcessing: { type: Boolean, default: false },
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const isLoading = ref(true);
const isDialogOpen = ref(false);
const isBusy = computed(() => isLoading.value || props.isProcessing);

// Product search
const productOptions = ref([]);
const selectedProduct = ref(null);
const selectedProductLabel = ref('');
const itemQuantity = ref(1);

// Items list
const posItems = ref([]);

async function loadProducts(search = '') {
    try {
        const res = await axios.get('/store-inventory/init-pos-products', {
            headers: { Accept: 'application/json' },
            params: { search },
        });
        productOptions.value = res.data.products ?? [];
    } catch {
        toast.error('Failed to load products.');
    }
}

// Cache label at selection time so debounced reload doesn't wipe it before addItem runs
watch(selectedProduct, (val) => {
    if (val) {
        const found = productOptions.value.find(p => p.value === val);
        if (found) selectedProductLabel.value = found.label;
    } else {
        selectedProductLabel.value = '';
    }
});

const addItem = () => {
    if (!selectedProduct.value) {
        toast.error('Please select a product.');
        return;
    }
    if (!itemQuantity.value || Number(itemQuantity.value) <= 0) {
        toast.error('Please enter a valid quantity.');
        return;
    }
    const product = productOptions.value.find(p => p.value === selectedProduct.value);
    posItems.value.push({
        product_id: Number(selectedProduct.value),
        product_name: selectedProductLabel.value || product?.label || '—',
        quantity: Number(itemQuantity.value),
        multiplier: product?.multiplier ?? 1,
    });
    selectedProduct.value = null;
    selectedProductLabel.value = '';
    itemQuantity.value = 1;
};

const removeItem = (index) => posItems.value.splice(index, 1);

const openConfirmDialog = () => {
    if (posItems.value.length === 0) {
        toast.error('Please add at least one item.');
        return;
    }
    isDialogOpen.value = true;
};

const handleAlertClose = () => { isDialogOpen.value = false; };

watch(() => props.isProcessing, (newVal, oldVal) => {
    if (oldVal === true && newVal === false) isDialogOpen.value = false;
});

const handleSubmit = () => {
    try {
        emit('handleSubmit', {
            items: posItems.value.map(item => ({
                product_id: item.product_id,
                pos_qty: Math.round(Number(item.quantity)),
            })),
        });
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
};

onMounted(async () => {
    isLoading.value = true;
    await loadProducts();
    isLoading.value = false;
});
</script>

<template>
    <FormCard :loading="false" size="lg">
        <form @submit.prevent class="space-y-3 mt-2">

            <p class="text-sm font-semibold">Initialize POS Quantities</p>
            <p class="text-xs text-muted-foreground -mt-2">Select products and specify initial quantity</p>

            <div class="grid w-full grid-cols-12 gap-2 items-end">
                <div class="col-span-7">
                    <FieldLabel class="font-normal text-xs">Product</FieldLabel>
                    <BaseCombobox v-model="selectedProduct" :options="productOptions" empty-message="No products found"
                        width="w-full" @search="loadProducts" placeholder="Search product..." />
                </div>
                <div class="col-span-3">
                    <FieldLabel class="font-normal text-xs">Qty</FieldLabel>
                    <Input v-model="itemQuantity" type="number" min="1" step="1" placeholder="1" />
                </div>
                <div class="col-span-2">
                    <BaseButton type="button" @click="addItem" :transactionType="'add'" :disabled="isBusy"
                        :skeleton="isLoading" class="w-full" />
                </div>
            </div>

            <div class="overflow-y-auto rounded-md border" style="max-height: 200px;">
                <Table class="text-xs">
                    <TableHeader>
                        <TableRow class="bg-muted/50">
                            <TableHead class="text-xs py-2">Product</TableHead>
                            <TableHead class="text-center w-24 text-xs py-2">Qty</TableHead>
                            <TableHead class="w-8" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="posItems.length === 0">
                            <TableCell colspan="3" class="text-center text-muted-foreground py-3 text-xs">
                                No items added yet.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="(item, index) in posItems" :key="index">
                            <TableCell class="whitespace-normal break-words min-w-0 text-xs py-1 leading-tight">
                                {{ item.product_name }}
                            </TableCell>
                            <TableCell class="text-center py-1">
                                <Input v-model.number="item.quantity" type="number" min="1" step="1"
                                    class="w-20 text-xs text-center mx-auto h-7" />
                            </TableCell>
                            <TableCell class="text-center py-1">
                                <button type="button" @click="removeItem(index)"
                                    class="text-destructive hover:opacity-70">
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

        </form>

        <template #footer>
            <BaseButton type="button" :disabled="isBusy" @click="emit('form-closed')" transactionType="cancel"
                :skeleton="isLoading" />
            <BaseButton type="button" :disabled="isBusy" @click="openConfirmDialog" transactionType="create"
                :loading="isProcessing" :skeleton="isLoading" />
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" transaction-type="create"
            @cancel="handleAlertClose" @confirm="handleSubmit" />
    </FormCard>
</template>
