<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Field, FieldGroup, FieldLabel, FieldSeparator } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import { useFieldGroupSkeleton } from '@/composables/useFieldGroupSkeleton';
import { useDateFormatter } from '@/composables/useDateFormatter';
import TransferStockItemsTable from './TransferStockItemsTable.vue';
import axios from 'axios';

const { normalizeDate } = useDateFormatter();

const props = defineProps({
    isProcessing: { type: Boolean, default: false },
    cardTitle: { type: String, default: 'Transfer Stock' },
    transactionType: { type: String, default: 'create' },
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const form = useForm({
    transfer_date: null,
    notes: '',
});

const isLoading = ref(false);
const isDialogOpen = ref(false);
const isBusy = computed(() => isLoading.value || props.isProcessing);

const { skeletonLayout } = useFieldGroupSkeleton([6, 12]);
const { skeletonLayout: skeletonLayoutItems } = useFieldGroupSkeleton([12]);

const openConfirmDialog = () => {
    if (transferItems.value.length === 0) {
        toast.error('Please add at least one item before saving.');
        return;
    }
    if (!form.transfer_date) {
        toast.error('Please select a transfer date.');
        return;
    }
    isDialogOpen.value = true;
};

const handleAlertClose = () => {
    isDialogOpen.value = false;
};

watch(() => props.isProcessing, (newVal, oldVal) => {
    if (oldVal === true && newVal === false) {
        isDialogOpen.value = false;
    }
});

const handleSubmit = () => {
    try {
        emit('handleSubmit', {
            ...form.data(),
            transfer_date: normalizeDate(form.transfer_date),
            items: transferItems.value.map(item => ({
                product_id: Number(item.product_id),
                lot_id: Number(item.lot_id),
                quantity: item.quantity,
                multiplier: item.multiplier,
            })),
        });
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
};

// ── Product search ──────────────────────────────────────────
const productsOptions = ref([]);
const selectedProduct = ref(null);
const lotOptions = ref([]);
const selectedLot = ref(null);
const itemQuantity = ref(1);
const transferItems = ref([]);

// Map of product_id → multiplier from API
const productMultiplierMap = ref({});

async function loadProducts(searchQuery = '') {
    try {
        const res = await axios.get('/products', {
            headers: { Accept: 'application/json' },
            params: { search: searchQuery },
        });
        productsOptions.value = res.data.products.map(p => ({
            value: String(p.id),
            label: p.display_name,
        }));
    } catch {
        toast.error('Failed to load products.');
    }
}

async function loadLots(productId) {
    lotOptions.value = [];
    selectedLot.value = null;
    if (!productId) return;
    try {
        const res = await axios.get(`/products/${productId}/lots`, {
            headers: { Accept: 'application/json' },
        });
        lotOptions.value = res.data.lots ?? [];
    } catch {
        toast.error('Failed to load lots.');
    }
}

async function fetchMultiplier(productId) {
    if (productMultiplierMap.value[productId] !== undefined) {
        return productMultiplierMap.value[productId];
    }
    try {
        const res = await axios.get('/products/' + productId + '/multiplier', {
            headers: { Accept: 'application/json' },
        });
        const multiplier = res.data.multiplier ?? 1;
        productMultiplierMap.value[productId] = multiplier;
        return multiplier;
    } catch {
        return 1;
    }
}

watch(selectedProduct, (newVal) => {
    loadLots(newVal);
});

const selectedLotData = computed(() => lotOptions.value.find(l => l.value === selectedLot.value) ?? null);

const addItem = async () => {
    if (!selectedProduct.value) {
        toast.error('Please select a product.');
        return;
    }
    if (!selectedLot.value) {
        toast.error('Please select a lot number.');
        return;
    }
    const lot = selectedLotData.value;
    if (!lot) { toast.error('Invalid lot selected.'); return; }
    if (Number(itemQuantity.value) <= 0) {
        toast.error('Quantity must be greater than 0.');
        return;
    }
    if (Number(itemQuantity.value) > lot.available_qty) {
        toast.error(`Quantity cannot exceed available lot qty (${lot.available_qty}).`);
        return;
    }
    const product = productsOptions.value.find(p => p.value === selectedProduct.value);
    const multiplier = await fetchMultiplier(selectedProduct.value);

    transferItems.value.push({
        product_id: selectedProduct.value,
        product_name: product?.label ?? selectedProduct.value,
        lot_id: lot.value,
        lot_number: lot.lot_number,
        quantity: Number(itemQuantity.value),
        multiplier: Number(multiplier),
    });

    selectedProduct.value = null;
    selectedLot.value = null;
    lotOptions.value = [];
    itemQuantity.value = 1;
};

const removeItem = (index) => {
    transferItems.value.splice(index, 1);
};

onMounted(async () => {
    isLoading.value = true;
    await loadProducts();
    isLoading.value = false;
});
</script>

<template>
    <FormCard :loading="false" size="3xl">
        <form @submit.prevent class="space-y-4 mt-4">
            <div class="grid grid-cols-12 gap-6 items-stretch">

                <!-- Left: Transfer Info -->
                <div class="col-span-4">
                    <BaseField legend="Transfer Information" description="Enter transfer details">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayout">
                                <div class="grid w-full grid-cols-12 gap-4">
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Transfer Date:</FieldLabel>
                                        <BaseDatePick v-model="form.transfer_date" class="w-32" />
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Notes:</FieldLabel>
                                        <Textarea v-model="form.notes" placeholder="Optional notes..." rows="4" />
                                    </Field>
                                </div>
                                <FieldSeparator />
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

                <!-- Right: Add Items + Table -->
                <div class="col-span-8 flex flex-col">
                    <BaseField legend="Items to Transfer"
                        description="Select products and quantities to move from warehouse to POS store"
                        class="flex flex-col flex-1">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayoutItems"
                                class="flex flex-col flex-1">
                                <div class="grid w-full grid-cols-12 gap-3">
                                    <Field class="col-span-5">
                                        <FieldLabel class="font-normal">Select Product:</FieldLabel>
                                        <BaseCombobox v-model="selectedProduct" :options="productsOptions"
                                            empty-message="No products found" width="w-full" @search="loadProducts"
                                            placeholder="Search product..." />
                                    </Field>
                                    <Field class="col-span-4">
                                        <FieldLabel class="font-normal">Lot Number: <span
                                                class="text-destructive">*</span></FieldLabel>
                                        <BaseCombobox v-model="selectedLot" :options="lotOptions"
                                            :disabled="!selectedProduct || lotOptions.length === 0"
                                            empty-message="No lots available" width="w-full"
                                            placeholder="Select lot..." />
                                    </Field>
                                    <Field class="col-span-2">
                                        <FieldLabel class="font-normal">
                                            Qty
                                            <span v-if="selectedLotData"
                                                class="text-xs text-muted-foreground ml-1">(max: {{
                                                selectedLotData.available_qty }})</span>
                                        </FieldLabel>
                                        <Input v-model="itemQuantity" type="number" min="1"
                                            :max="selectedLotData?.available_qty" step="1" placeholder="1" />
                                    </Field>
                                    <Field class="col-span-1">
                                        <FieldLabel class="invisible">-</FieldLabel>
                                        <BaseButton type="button" @click="addItem" :transactionType="'add'"
                                            :disabled="isBusy" :skeleton="isLoading" />
                                    </Field>
                                </div>

                                <TransferStockItemsTable :items="transferItems" @remove="removeItem"
                                    class="flex-1 min-h-0" />
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

            </div>
        </form>

        <template #footer>
            <BaseButton type="button" :disabled="isBusy" @click="emit('form-closed')" transactionType="cancel"
                :skeleton="isLoading" />
            <BaseButton type="button" @click="openConfirmDialog" transactionType="create" :loading="isProcessing"
                :disabled="isBusy" :skeleton="isLoading" />
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" transaction-type="create"
            @cancel="handleAlertClose" @confirm="handleSubmit" />
    </FormCard>
</template>
