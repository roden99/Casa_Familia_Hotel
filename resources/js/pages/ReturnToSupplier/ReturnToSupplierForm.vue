<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import BaseField from '@/components/BaseField.vue';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    Table, TableBody, TableCell, TableHead,
    TableHeader, TableRow,
} from '@/components/ui/table';
import { X, Tag } from 'lucide-vue-next';
import { ref, computed, watch, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';
import { useFieldGroupSkeleton } from '@/composables/useFieldGroupSkeleton';
import { useDateFormatter } from '@/composables/useDateFormatter';

const { normalizeDate } = useDateFormatter();

const props = defineProps({
    isProcessing: { type: Boolean, default: false },
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const { skeletonLayout } = useFieldGroupSkeleton([6, 12, 12]);
const { skeletonLayout: skeletonLayoutItems } = useFieldGroupSkeleton([12]);

const isLoading = ref(false);
const isDialogOpen = ref(false);
const isBusy = computed(() => isLoading.value || props.isProcessing);

// Header fields
const supplierOptions = ref([]);
const selectedSupplier = ref(null);
const returnDate = ref(null);
const notes = ref('');

// Item row fields
const productOptions = ref([]);
const selectedProduct = ref(null);
const lotOptions = ref([]);
const selectedLot = ref(null);
const itemQty = ref(1);

// Line items list
const returnItems = ref([]);

async function loadSuppliers(search = '') {
    try {
        const res = await axios.get('/suppliers', {
            headers: { Accept: 'application/json' },
            params: { search },
        });
        supplierOptions.value = res.data.suppliers.map(s => ({
            value: String(s.id),
            label: s.company.toUpperCase(),
        }));
    } catch {
        toast.error('Failed to load suppliers.');
    }
}

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

watch(selectedProduct, (val) => loadLots(val));

const selectedLotData = computed(() =>
    lotOptions.value.find(l => l.value === selectedLot.value) ?? null
);

const addItem = () => {
    if (!selectedProduct.value) { toast.error('Please select a product.'); return; }
    if (!selectedLot.value) { toast.error('Please select a lot.'); return; }
    if (Number(itemQty.value) < 1) { toast.error('Quantity must be at least 1.'); return; }

    const lot = selectedLotData.value;
    if (Number(itemQty.value) > lot.available_qty) {
        toast.error(`Quantity cannot exceed available qty (${lot.available_qty}).`);
        return;
    }

    const product = productOptions.value.find(p => p.value === selectedProduct.value);

    returnItems.value.push({
        product_id: selectedProduct.value,
        product_name: product?.label ?? `Product #${selectedProduct.value}`,
        lot_id: lot.value,
        lot_number: lot.lot_number,
        expiration_date: lot.expiration_date,
        available_qty: lot.available_qty,
        quantity: Number(itemQty.value),
    });

    selectedProduct.value = null;
    selectedLot.value = null;
    lotOptions.value = [];
    itemQty.value = 1;
};

const removeItem = (index) => {
    returnItems.value.splice(index, 1);
};

const openConfirmDialog = () => {
    if (!selectedSupplier.value) { toast.error('Please select a supplier.'); return; }
    if (!returnDate.value) { toast.error('Please select a return date.'); return; }
    if (returnItems.value.length === 0) { toast.error('Please add at least one item.'); return; }
    isDialogOpen.value = true;
};

watch(() => props.isProcessing, (newVal, oldVal) => {
    if (oldVal === true && newVal === false) isDialogOpen.value = false;
});

const handleSubmit = () => {
    emit('handleSubmit', {
        supplier_id: Number(selectedSupplier.value),
        return_date: normalizeDate(returnDate.value),
        notes: notes.value || null,
        items: returnItems.value.map(i => ({
            product_id: Number(i.product_id),
            lot_id: Number(i.lot_id),
            quantity: i.quantity,
        })),
    });
};

onMounted(async () => {
    isLoading.value = true;
    await Promise.all([loadSuppliers(), loadProducts()]);
    isLoading.value = false;
});
</script>

<template>
    <FormCard :loading="false" size="4xl" cardTitle="New Return to Supplier">
        <form @submit.prevent class="space-y-4 mt-4">
            <div class="grid grid-cols-12 gap-6 items-stretch">

                <!-- Left: Return Info -->
                <div class="col-span-4">
                    <BaseField legend="Return Information" description="Enter return details">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayout">
                                <div class="grid w-full grid-cols-12 gap-4">
                                    <Field class="col-span-12">
                                        <FieldLabel>Supplier <span class="text-destructive">*</span></FieldLabel>
                                        <BaseCombobox v-model="selectedSupplier" :options="supplierOptions"
                                            empty-message="No suppliers found" width="w-full" @search="loadSuppliers"
                                            placeholder="Search supplier..." :disabled="isBusy" />
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel>Return Date <span class="text-destructive">*</span></FieldLabel>
                                        <BaseDatePick v-model="returnDate" :disabled="isBusy" />
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel>Notes</FieldLabel>
                                        <Textarea v-model="notes" placeholder="Optional notes..." rows="4"
                                            :disabled="isBusy" />
                                    </Field>
                                </div>
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

                <!-- Right: Add Items -->
                <div class="col-span-8 flex flex-col">
                    <BaseField legend="Items to Return"
                        description="Select products and lot numbers from current inventory"
                        class="flex flex-col flex-1">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayoutItems"
                                class="flex flex-col flex-1">

                                <!-- Add item row -->
                                <div class="grid w-full grid-cols-12 gap-3">
                                    <Field class="col-span-5">
                                        <FieldLabel class="font-normal">Product:</FieldLabel>
                                        <BaseCombobox v-model="selectedProduct" :options="productOptions"
                                            empty-message="No products found" width="w-full" @search="loadProducts"
                                            placeholder="Search product..." />
                                    </Field>
                                    <Field class="col-span-4">
                                        <FieldLabel class="font-normal">
                                            Lot:
                                            <span v-if="selectedLotData" class="text-xs text-muted-foreground ml-1">
                                                (avail: {{ selectedLotData.available_qty }})
                                            </span>
                                        </FieldLabel>
                                        <BaseCombobox v-model="selectedLot" :options="lotOptions"
                                            :disabled="!selectedProduct || lotOptions.length === 0"
                                            empty-message="No lots available" width="w-full"
                                            placeholder="Select lot..." />
                                    </Field>
                                    <Field class="col-span-2">
                                        <FieldLabel class="font-normal">Qty:</FieldLabel>
                                        <Input v-model.number="itemQty" type="number" min="1"
                                            :max="selectedLotData?.available_qty" step="1" placeholder="1"
                                            :disabled="isBusy" />
                                    </Field>
                                    <Field class="col-span-1">
                                        <FieldLabel class="invisible">-</FieldLabel>
                                        <BaseButton type="button" @click="addItem" transactionType="add"
                                            :disabled="isBusy" :skeleton="isLoading" />
                                    </Field>
                                </div>

                                <!-- Items table -->
                                <div class="overflow-y-auto rounded-md border flex-1 min-h-0 mt-2 max-h-72">
                                    <Table class="text-xs">
                                        <TableHeader class="sticky top-0 z-10">
                                            <TableRow>
                                                <TableHead class="text-xs">Product</TableHead>
                                                <TableHead class="text-xs w-28">Lot No.</TableHead>
                                                <TableHead class="text-xs w-24">Expiry</TableHead>
                                                <TableHead class="text-xs text-center w-20">Avail</TableHead>
                                                <TableHead class="text-xs text-center w-16">Qty</TableHead>
                                                <TableHead class="w-8" />
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-if="returnItems.length === 0">
                                                <TableCell colspan="6"
                                                    class="text-center text-muted-foreground text-xs py-6">
                                                    No items added yet.
                                                </TableCell>
                                            </TableRow>
                                            <TableRow v-for="(item, i) in returnItems" :key="i">
                                                <TableCell class="text-xs whitespace-normal break-words min-w-0">
                                                    {{ item.product_name }}
                                                </TableCell>
                                                <TableCell class="text-xs">
                                                    <span class="inline-flex items-center gap-1 font-mono">
                                                        <Tag class="h-3 w-3 text-amber-500 shrink-0" />
                                                        {{ item.lot_number }}
                                                    </span>
                                                </TableCell>
                                                <TableCell class="text-xs text-muted-foreground">
                                                    {{ item.expiration_date ?? '—' }}
                                                </TableCell>
                                                <TableCell class="text-xs text-center text-muted-foreground">
                                                    {{ item.available_qty }}
                                                </TableCell>
                                                <TableCell class="text-xs text-center font-medium">
                                                    {{ item.quantity }}
                                                </TableCell>
                                                <TableCell class="text-center">
                                                    <button type="button" @click="removeItem(i)"
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
