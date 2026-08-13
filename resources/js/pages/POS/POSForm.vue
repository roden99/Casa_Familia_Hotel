<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Field, FieldGroup, FieldLabel, FieldSeparator } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';
import { useFieldGroupSkeleton } from '@/composables/useFieldGroupSkeleton';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import POSItemsTable from './POSItemsTable.vue';
import axios from 'axios';

const props = defineProps({
    isProcessing: { type: Boolean, default: false },
    cardTitle: { type: String, default: 'POS Transaction' },
    transactionType: { type: String, default: 'create' },
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const form = useForm({
    receipt_no: '',
    customer_id: '',
    payment_method: 'cash',
    notes: '',
});

const isDialogOpen = ref(false);
const isLoading = ref(false);
const isBusy = computed(() => isLoading.value || props.isProcessing);

const { skeletonLayout } = useFieldGroupSkeleton([10, 2, 4, 4, 4, 4]);
const { skeletonLayout: skeletonLayoutItems } = useFieldGroupSkeleton([12]);

const paymentOptions = [
    { value: 'cash', label: 'Cash' },
    { value: 'card', label: 'Card' },
    { value: 'gcash', label: 'GCash' },
    { value: 'others', label: 'Others' },
];

const handleAlertClose = () => {
    isDialogOpen.value = false;
};

const openConfirmDialog = () => {
    if (orderItems.value.length === 0) {
        toast.error('Please add at least one item before saving.');
        return;
    }
    isDialogOpen.value = true;
};

watch(() => props.isProcessing, (newVal, oldVal) => {
    if (oldVal === true && newVal === false) {
        isDialogOpen.value = false;
    }
});

const handleSubmit = () => {
    try {
        if (orderItems.value.length === 0) {
            toast.error('Please add at least one item before saving.');
            return;
        }
        emit('handleSubmit', {
            ...form.data(),
            customer_id: form.customer_id ? Number(form.customer_id) : null,
            items: orderItems.value.map(item => ({
                lot_id:              Number(item.lot_id),
                product_name:        item.product_name,
                lot_number:          item.lot_number,
                expiration_date:     item.expiration_date,
                quantity:            item.quantity,
                unit_price:          item.unit_price,
                discount_percentage: item.discount_percentage ?? 0,
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

// ─── Products combobox ────────────────────────────────────────────────────────
const productOptions = ref([]);

async function loadProducts(searchQuery = '') {
    try {
        const res = await axios.get('/pos-products', {
            headers: { Accept: 'application/json' },
            params: { search: searchQuery },
        });
        productOptions.value = res.data.products ?? [];
    } catch {
        toast.error('Failed to load products.');
    }
}

// ─── Items management ─────────────────────────────────────────────────────────
const selectedProduct = ref(null);
const itemQuantity = ref(1);
const itemPrice = ref(0);
const itemDiscount = ref(0);
const orderItems = ref([]);

const totalAmount = computed(() => {
    const raw = orderItems.value.reduce((sum, item) => {
        const disc = Number(item.discount_percentage) || 0;
        return sum + Number(item.quantity) * Number(item.unit_price) * (1 - disc / 100);
    }, 0);
    return raw.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
});

// Auto-fill selling price when a lot/product entry is selected
watch(selectedProduct, (newVal) => {
    if (!newVal) return;
    const entry = productOptions.value.find(p => p.value === newVal);
    itemPrice.value = entry?.pos_selling_price ?? 0;
});

const addItem = () => {
    if (!selectedProduct.value) {
        toast.error('Please select a product.');
        return;
    }
    const entry = productOptions.value.find(p => p.value === selectedProduct.value);
    orderItems.value.push({
        lot_id:              selectedProduct.value,
        product_name:        entry?.product_name ?? entry?.label ?? selectedProduct.value,
        lot_number:          entry?.lot_number ?? null,
        expiration_date:     entry?.expiration_date ?? null,
        pos_qty:             entry?.pos_qty ?? 0,
        quantity:            Number(itemQuantity.value),
        unit_price:          Number(itemPrice.value),
        discount_percentage: Number(itemDiscount.value) || 0,
    });
    selectedProduct.value = null;
    itemQuantity.value = 1;
    itemPrice.value = 0;
    itemDiscount.value = 0;
};

const removeItem = (index) => {
    orderItems.value.splice(index, 1);
};
</script>

<template>
    <FormCard :loading="false" size="3xl">
        <form @submit.prevent class="space-y-4 mt-4">
            <div class="grid grid-cols-12 gap-6 items-stretch">

                <!-- Left: Transaction Info -->
                <div class="col-span-4">
                    <BaseField legend="Transaction Details" description="Fill in POS transaction info">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayout">
                                <div class="grid w-full grid-cols-12 gap-4">

                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Receipt No.:</FieldLabel>
                                        <Input v-model="form.receipt_no" placeholder="e.g. RCP-001" />
                                    </Field>

                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Payment Method:</FieldLabel>
                                        <select v-model="form.payment_method"
                                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                            <option v-for="opt in paymentOptions" :key="opt.value" :value="opt.value">
                                                {{ opt.label }}
                                            </option>
                                        </select>
                                    </Field>

                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Notes:</FieldLabel>
                                        <Input v-model="form.notes" placeholder="Optional notes" />
                                    </Field>

                                </div>
                                <FieldSeparator />
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

                <!-- Right: Add Item + Items Table -->
                <div class="col-span-8 flex flex-col">
                    <BaseField legend="Items" description="Add products to this transaction"
                        class="flex flex-col flex-1">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayoutItems"
                                class="flex flex-col flex-1">
                                <div class="grid w-full grid-cols-12 gap-3">
                                    <Field class="col-span-6">
                                        <FieldLabel class="font-normal">Product / Lot:</FieldLabel>
                                        <BaseCombobox v-model="selectedProduct" :options="productOptions"
                                            empty-message="No products found" width="w-full"
                                            placeholder="Search by name, code or lot..." @search="loadProducts" />
                                    </Field>
                                    <Field class="col-span-2">
                                        <FieldLabel class="font-normal">Qty</FieldLabel>
                                        <Input v-model="itemQuantity" type="number" min="1" step="1"
                                            placeholder="1" />
                                    </Field>
                                    <Field class="col-span-2">
                                        <FieldLabel class="font-normal">Unit Price</FieldLabel>
                                        <Input v-model="itemPrice" type="number" min="0" step="0.01"
                                            placeholder="0.00" />
                                    </Field>
                                    <Field class="col-span-1">
                                        <FieldLabel class="font-normal">Disc %</FieldLabel>
                                        <Input v-model="itemDiscount" type="number" min="0" max="100" step="0.01"
                                            placeholder="0" />
                                    </Field>
                                    <Field class="col-span-1">
                                        <FieldLabel class="invisible">-</FieldLabel>
                                        <BaseButton type="button" @click="addItem" :transactionType="'add'"
                                            :disabled="isBusy" :skeleton="isLoading" />
                                    </Field>
                                </div>

                                <POSItemsTable :items="orderItems" @remove="removeItem" class="flex-1 min-h-0" />
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

            </div>
        </form>

        <template #footer>
            <div class="mr-auto flex flex-col">
                <span class="text-xs text-muted-foreground uppercase tracking-wide">Total Amount</span>
                <span class="text-lg font-bold">{{ totalAmount }}</span>
            </div>

            <BaseButton type="button" :disabled="isBusy" @click="emit('form-closed')" transactionType="cancel"
                :skeleton="isLoading" />
            <BaseButton type="button" @click="openConfirmDialog" :transactionType="props.transactionType"
                :loading="isProcessing" :disabled="isBusy" :skeleton="isLoading" />
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" :transaction-type="props.transactionType"
            @cancel="handleAlertClose" @confirm="handleSubmit" />
    </FormCard>
</template>
