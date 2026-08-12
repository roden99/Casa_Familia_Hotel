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
import PosDeliveryItemsTable from './PosDeliveryItemsTable.vue';
import axios from 'axios';

const { normalizeDate } = useDateFormatter();

const props = defineProps({
    isProcessing: { type: Boolean, default: false },
    suppliers: { type: Array, default: () => [] },
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const form = useForm({
    supplier_id:   null,
    invoice_no:    '',
    delivery_date: null,
    notes:         '',
});

const isLoading  = ref(false);
const isDialogOpen = ref(false);
const isBusy = computed(() => isLoading.value || props.isProcessing);

const { skeletonLayout }       = useFieldGroupSkeleton([6, 6, 6, 12]);
const { skeletonLayout: skeletonLayoutItems } = useFieldGroupSkeleton([12]);

const supplierOptions = computed(() =>
    props.suppliers.map(s => ({ value: String(s.id), label: s.company }))
);

// ── Items ─────────────────────────────────────────────────────────────────────
const productOptions  = ref([]);
const selectedProduct = ref(null);
const itemLotNumber   = ref('');
const itemExpiry      = ref('');
const itemQty         = ref(1);
const itemCost        = ref('');
const itemSellPrice   = ref('');
const deliveryItems   = ref([]);

async function loadProducts(search = '') {
    try {
        const res = await axios.get('/products', {
            headers: { Accept: 'application/json' },
            params: { search },
        });
        productOptions.value = res.data.products.map(p => ({
            value: String(p.id),
            label: p.display_name,
        }));
    } catch {
        toast.error('Failed to load products.');
    }
}

const addItem = () => {
    if (!selectedProduct.value) { toast.error('Please select a product.'); return; }
    if (!itemLotNumber.value.trim()) { toast.error('Please enter a lot number.'); return; }
    if (Number(itemQty.value) <= 0) { toast.error('Quantity must be greater than 0.'); return; }

    const product = productOptions.value.find(p => p.value === selectedProduct.value);
    deliveryItems.value.push({
        product_id:      selectedProduct.value,
        product_name:    product?.label ?? selectedProduct.value,
        lot_number:      itemLotNumber.value.trim(),
        expiration_date: itemExpiry.value ? normalizeDate(itemExpiry.value) : null,
        quantity:        Number(itemQty.value),
        cost:            itemCost.value !== '' ? Number(itemCost.value) : null,
        selling_price:   itemSellPrice.value !== '' ? Number(itemSellPrice.value) : null,
    });
    selectedProduct.value = null;
    itemLotNumber.value   = '';
    itemExpiry.value      = '';
    itemQty.value         = 1;
    itemCost.value        = '';
    itemSellPrice.value   = '';
};

const removeItem = (index) => deliveryItems.value.splice(index, 1);

const openConfirmDialog = () => {
    if (deliveryItems.value.length === 0) { toast.error('Please add at least one item.'); return; }
    if (!form.delivery_date) { toast.error('Please select a delivery date.'); return; }
    isDialogOpen.value = true;
};

watch(() => props.isProcessing, (newVal, oldVal) => {
    if (oldVal === true && newVal === false) isDialogOpen.value = false;
});

const handleSubmit = () => {
    try {
        emit('handleSubmit', {
            ...form.data(),
            supplier_id:   form.supplier_id ? Number(form.supplier_id) : null,
            delivery_date: normalizeDate(form.delivery_date),
            items: deliveryItems.value.map(item => ({
                product_id:      Number(item.product_id),
                lot_number:      item.lot_number,
                expiration_date: item.expiration_date,
                quantity:        item.quantity,
                cost:            item.cost,
                selling_price:   item.selling_price,
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
    <FormCard :loading="false" size="4xl">
        <form @submit.prevent class="space-y-4 mt-4">
            <div class="grid grid-cols-12 gap-6 items-stretch">

                <!-- Left: Delivery Info -->
                <div class="col-span-4">
                    <BaseField legend="Delivery Information" description="Supplier and invoice details">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayout">
                                <div class="grid w-full grid-cols-12 gap-4">
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Supplier:</FieldLabel>
                                        <BaseCombobox v-model="form.supplier_id" :options="supplierOptions"
                                            empty-message="No suppliers found" width="w-full"
                                            placeholder="Select supplier..." />
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Invoice No.:</FieldLabel>
                                        <Input v-model="form.invoice_no" placeholder="e.g. INV-2026-001" />
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Delivery Date: <span class="text-destructive">*</span></FieldLabel>
                                        <BaseDatePick v-model="form.delivery_date" />
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Notes:</FieldLabel>
                                        <Textarea v-model="form.notes" placeholder="Optional notes..." rows="3" />
                                    </Field>
                                </div>
                                <FieldSeparator />
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

                <!-- Right: Add Items -->
                <div class="col-span-8 flex flex-col">
                    <BaseField legend="Items Delivered"
                        description="Products delivered directly to POS store"
                        class="flex flex-col flex-1">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayoutItems"
                                class="flex flex-col flex-1">

                                <!-- Row 1: Product / Lot / Expiry -->
                                <div class="grid w-full grid-cols-12 gap-3">
                                    <Field class="col-span-5">
                                        <FieldLabel class="font-normal">Product:</FieldLabel>
                                        <BaseCombobox v-model="selectedProduct" :options="productOptions"
                                            empty-message="No products found" width="w-full"
                                            @search="loadProducts" placeholder="Search product..." />
                                    </Field>
                                    <Field class="col-span-3">
                                        <FieldLabel class="font-normal">Lot No.: <span class="text-destructive">*</span></FieldLabel>
                                        <Input v-model="itemLotNumber" placeholder="e.g. LOT-001" />
                                    </Field>
                                    <Field class="col-span-4">
                                        <FieldLabel class="font-normal">Expiry Date:</FieldLabel>
                                        <BaseDatePick v-model="itemExpiry" />
                                    </Field>
                                </div>

                                <!-- Row 2: Qty / Cost / Selling Price / Add -->
                                <div class="grid w-full grid-cols-12 gap-3 mt-1">
                                    <Field class="col-span-3">
                                        <FieldLabel class="font-normal">Qty:</FieldLabel>
                                        <Input v-model="itemQty" type="number" min="0.0001" step="0.0001" placeholder="1" />
                                    </Field>
                                    <Field class="col-span-3">
                                        <FieldLabel class="font-normal">Cost:</FieldLabel>
                                        <Input v-model="itemCost" type="number" min="0" step="0.01" placeholder="0.00" />
                                    </Field>
                                    <Field class="col-span-3">
                                        <FieldLabel class="font-normal">Selling Price:</FieldLabel>
                                        <Input v-model="itemSellPrice" type="number" min="0" step="0.01" placeholder="0.00" />
                                    </Field>
                                    <Field class="col-span-3">
                                        <FieldLabel class="invisible">-</FieldLabel>
                                        <BaseButton type="button" @click="addItem" :transactionType="'add'"
                                            :disabled="isBusy" :skeleton="isLoading" />
                                    </Field>
                                </div>

                                <PosDeliveryItemsTable :items="deliveryItems" @remove="removeItem"
                                    class="flex-1 min-h-0 mt-2" />
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
            @cancel="isDialogOpen = false" @confirm="handleSubmit" />
    </FormCard>
</template>
