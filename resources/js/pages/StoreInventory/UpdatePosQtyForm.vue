<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import { useFieldGroupSkeleton } from '@/composables/useFieldGroupSkeleton';
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

const { skeletonLayout: skeletonLayoutItems } = useFieldGroupSkeleton([12]);

// Product search
const productOptions = ref([]);
const selectedProduct = ref(null);
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

watch(selectedProduct, () => { /* reset if needed */ });

const addItem = () => {
    if (!selectedProduct.value) {
        toast.error('Please select a product.');
        return;
    }
    if (posItems.value.some(i => i.product_id === Number(selectedProduct.value))) {
        toast.warning('Product already added.', { description: 'Remove the existing entry first.' });
        return;
    }
    if (!itemQuantity.value || Number(itemQuantity.value) <= 0) {
        toast.error('Please enter a valid quantity.');
        return;
    }
    const product = productOptions.value.find(p => p.value === selectedProduct.value);
    posItems.value.push({
        product_id: Number(selectedProduct.value),
        product_name: product?.label ?? '—',
        quantity: Number(itemQuantity.value),
        multiplier: product?.multiplier ?? 1,
        selling_price: product?.pos_selling_price ?? '',
    });
    selectedProduct.value = null;
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
                pos_qty: parseFloat((item.quantity * item.multiplier).toFixed(4)),
                pos_selling_price: item.selling_price !== '' ? Number(item.selling_price) : null,
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
    <FormCard :loading="false" size="xl">
        <form @submit.prevent class="space-y-3 mt-2">

            <div class="flex flex-col">
                <BaseField legend="Initialize POS Quantities"
                    description="Select products with available inventory, specify quantity">
                    <template #fields>
                        <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayoutItems">
                            <div class="grid w-full grid-cols-12 gap-3">
                                <Field class="col-span-7">
                                    <FieldLabel class="font-normal">Select Product:</FieldLabel>
                                    <BaseCombobox v-model="selectedProduct" :options="productOptions"
                                        empty-message="No products with available qty" width="w-full"
                                        @search="loadProducts" placeholder="Search product..." />
                                </Field>
                                <Field class="col-span-2">
                                    <FieldLabel class="font-normal">Qty</FieldLabel>
                                    <Input v-model="itemQuantity" type="number" min="0.0001" step="0.0001"
                                        placeholder="1" />
                                </Field>
                                <Field class="col-span-2">
                                    <FieldLabel class="invisible">-</FieldLabel>
                                    <BaseButton type="button" @click="addItem" :transactionType="'add'"
                                        :disabled="isBusy" :skeleton="isLoading" />
                                </Field>
                            </div>

                            <div class="overflow-y-auto rounded-md border mt-2" style="max-height: 220px;">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Product</TableHead>
                                            <TableHead class="text-center w-20">Qty</TableHead>
                                            <TableHead class="text-center w-20">Multiplier</TableHead>
                                            <TableHead class="text-center w-24">POS Qty</TableHead>
                                            <TableHead class="text-center w-28">Selling Price</TableHead>
                                            <TableHead class="w-8" />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-if="posItems.length === 0">
                                            <TableCell colspan="6" class="text-center text-muted-foreground py-4">
                                                No items added yet.
                                            </TableCell>
                                        </TableRow>
                                        <TableRow v-for="(item, index) in posItems" :key="index">
                                            <TableCell class="whitespace-normal break-words min-w-0 text-sm">
                                                {{ item.product_name }}
                                            </TableCell>
                                            <TableCell class="text-center">
                                                <Input v-model.number="item.quantity" type="number" min="0.0001"
                                                    step="0.0001" class="w-16 text-center mx-auto" />
                                            </TableCell>
                                            <TableCell class="text-center text-sm font-medium text-muted-foreground">
                                                {{ item.multiplier }}
                                            </TableCell>
                                            <TableCell class="text-center font-medium text-teal-600">
                                                {{ (item.quantity * item.multiplier).toFixed(4) }}
                                            </TableCell>
                                            <TableCell class="text-center">
                                                <Input v-model.number="item.selling_price" type="number" min="0"
                                                    step="0.01" placeholder="0.00" class="w-24 text-center mx-auto" />
                                            </TableCell>
                                            <TableCell class="text-center">
                                                <button type="button" @click="removeItem(index)"
                                                    class="text-destructive hover:opacity-70">
                                                    <X class="h-4 w-4" />
                                                </button>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </FieldGroup>
                    </template>
                </BaseField>
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
