<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import { Input } from '@/components/ui/input';
import { ref, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { Layers, Pencil, Check, X, AlertTriangle } from 'lucide-vue-next';
import axios from 'axios';

const { normalizeDate } = useDateFormatter();

const props = defineProps({
    product: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isLoading = ref(true);
const lots = ref([]);
const isProcessing = ref(false);
const isFormDialogOpen = ref(false);

const editingLotId = ref(null);
const editCost = ref(0);
const editSellingPrice = ref('');
const isSavingEdit = ref(false);

const form = ref({ lot_number: '', expiration_date: null, quantity: 0, cost: 0, selling_price: '' });

const loadLots = async () => {
    isLoading.value = true;
    try {
        const res = await axios.get(`/pos-products/${props.product.id}/lots`, {
            headers: { Accept: 'application/json' },
        });
        lots.value = res.data.lots ?? [];
    } catch {
        toast.error('Failed to load POS lots.');
    } finally {
        isLoading.value = false;
    }
};

const openEdit = (lot) => {
    editingLotId.value = lot.id;
    editCost.value = lot.cost ?? 0;
    editSellingPrice.value = lot.selling_price !== null ? lot.selling_price : '';
};

const cancelEdit = () => { editingLotId.value = null; };

const saveEdit = async (lot) => {
    isSavingEdit.value = true;
    try {
        await axios.patch(`/pos-products/${props.product.id}/lots/${lot.id}`, {
            cost: editCost.value !== '' ? Number(editCost.value) : 0,
            selling_price: editSellingPrice.value !== '' ? Number(editSellingPrice.value) : null,
        }, { headers: { Accept: 'application/json' } });
        toast.success('Lot updated.');
        editingLotId.value = null;
        await loadLots();
    } catch {
        toast.error('Failed to update lot.');
    } finally {
        isSavingEdit.value = false;
    }
};

const openFormDialog = () => {
    if (!form.value.lot_number.trim()) { toast.error('Please enter a lot number.'); return; }
    if (!form.value.expiration_date) { toast.error('Please select an expiration date.'); return; }
    if (Number(form.value.quantity) <= 0) { toast.error('Quantity must be greater than zero.'); return; }
    const maxQty = Number(props.product.pos_qty ?? 0);
    if (Number(form.value.quantity) > maxQty) {
        toast.error(`Quantity cannot exceed current inventory (${maxQty}).`);
        return;
    }
    isFormDialogOpen.value = true;
};

const submitLot = async () => {
    isProcessing.value = true;
    try {
        await axios.post(`/pos-products/${props.product.id}/lots`, {
            lot_number: form.value.lot_number,
            expiration_date: normalizeDate(form.value.expiration_date),
            quantity: form.value.quantity,
            cost: form.value.cost,
            selling_price: form.value.selling_price !== '' ? Number(form.value.selling_price) : null,
        }, { headers: { Accept: 'application/json' } });
        toast.success('Lot added successfully!');
        isFormDialogOpen.value = false;
        form.value = { lot_number: '', expiration_date: null, quantity: 0, cost: 0, selling_price: '' };
        await loadLots();
    } catch (err) {
        const errors = err.response?.data?.errors;
        const msg = errors ? Object.values(errors)[0][0] : 'Failed to add lot.';
        toast.warning('Failed to add lot.', { description: msg });
    } finally {
        isProcessing.value = false;
    }
};

const fmt = (val) =>
    Number(val).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

onMounted(loadLots);
</script>

<template>
    <FormCard size="2xl" :loading="isProcessing">
        <div class="mt-4 space-y-4">

            <div class="flex items-center justify-between gap-4 rounded-md border bg-muted/40 px-4 py-3">
                <div class="flex items-center gap-2 min-w-0">
                    <Layers class="h-4 w-4 text-indigo-500 shrink-0" />
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground uppercase tracking-wide font-medium">POS Product</p>
                        <p class="text-sm font-semibold truncate">
                            {{ product?.display_name ?? product?.productname ?? '—' }}
                        </p>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-xs text-muted-foreground uppercase tracking-wide font-medium">Total Lots</p>
                    <p class="text-sm font-bold">{{ lots.length }}</p>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4">

                <!-- Left: Lots list -->
                <div class="col-span-7">
                    <BaseField legend="POS Lots" description="Lot/batch records tracked in POS inventory">
                        <template #fields>
                            <FieldGroup :skeleton="isLoading" :skeleton-rows="2" :skeleton-cols="3">
                                <div v-if="!isLoading && lots.length === 0"
                                    class="flex flex-col items-center justify-center py-10 text-muted-foreground gap-2">
                                    <Layers class="h-8 w-8 opacity-30" />
                                    <p class="text-sm">No POS lots recorded yet.</p>
                                </div>
                                <div v-else class="overflow-y-auto max-h-72 rounded-md border">
                                    <table class="w-full text-xs">
                                        <thead class="sticky top-0 bg-muted/80 z-10">
                                            <tr>
                                                <th class="text-left px-3 py-2 font-semibold">Lot No.</th>
                                                <th class="text-left px-3 py-2 font-semibold">Expiry</th>
                                                <th class="text-right px-3 py-2 font-semibold">Cost</th>
                                                <th class="text-right px-3 py-2 font-semibold">Selling Price</th>
                                                <th class="text-right px-3 py-2 font-semibold">Qty</th>
                                                <th class="w-8 px-2 py-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="lot in lots" :key="lot.id"
                                                class="border-t hover:bg-muted/30 transition-colors"
                                                :class="lot.is_expired ? 'bg-red-50 dark:bg-red-950/20' : ''">
                                                <td class="px-3 py-2 font-medium font-mono">{{ lot.lot_number }}</td>
                                                <td class="px-3 py-2">
                                                    <div class="flex items-center gap-1">
                                                        <span
                                                            :class="lot.is_expired ? 'text-red-600 font-semibold' : ''">
                                                            {{ lot.expiration_date }}
                                                        </span>
                                                        <AlertTriangle v-if="lot.is_expired"
                                                            class="h-3 w-3 text-red-500 shrink-0" />
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono">
                                                    <template v-if="editingLotId === lot.id">
                                                        <input v-model="editCost" type="number" min="0" step="0.01"
                                                            class="w-20 text-right border rounded px-1 py-0.5 text-xs" />
                                                    </template>
                                                    <template v-else>{{ fmt(lot.cost ?? 0) }}</template>
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono">
                                                    <template v-if="editingLotId === lot.id">
                                                        <input v-model="editSellingPrice" type="number" min="0"
                                                            step="0.01"
                                                            class="w-20 text-right border rounded px-1 py-0.5 text-xs"
                                                            placeholder="—" />
                                                    </template>
                                                    <template v-else>{{ lot.selling_price !== null ?
                                                        fmt(lot.selling_price) : '—' }}</template>
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono">{{ fmt(lot.quantity) }}</td>
                                                <td class="px-2 py-2 text-center">
                                                    <template v-if="editingLotId === lot.id">
                                                        <div class="flex items-center justify-center gap-1">
                                                            <button type="button" @click="saveEdit(lot)"
                                                                :disabled="isSavingEdit"
                                                                class="text-green-600 hover:opacity-70 disabled:opacity-40">
                                                                <Check class="h-3.5 w-3.5" />
                                                            </button>
                                                            <button type="button" @click="cancelEdit"
                                                                class="text-muted-foreground hover:text-destructive">
                                                                <X class="h-3.5 w-3.5" />
                                                            </button>
                                                        </div>
                                                    </template>
                                                    <template v-else>
                                                        <button type="button" @click="openEdit(lot)"
                                                            class="text-muted-foreground hover:text-foreground transition-colors">
                                                            <Pencil class="h-3.5 w-3.5" />
                                                        </button>
                                                    </template>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

                <!-- Right: Add lot form -->
                <div class="col-span-5">
                    <BaseField legend="Add New Lot" description="Record a new POS lot for this product">
                        <template #fields>
                            <FieldGroup>
                                <div class="grid w-full grid-cols-12 gap-3">
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Lot Number: <span
                                                class="text-destructive">*</span></FieldLabel>
                                        <Input v-model="form.lot_number" placeholder="e.g. LOT-2026-001" />
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Expiration Date: <span
                                                class="text-destructive">*</span></FieldLabel>
                                        <BaseDatePick v-model="form.expiration_date" />
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">
                                            Quantity:
                                            <span v-if="product.pos_qty !== undefined"
                                                class="text-xs text-muted-foreground ml-1">(max: {{ product.pos_qty
                                                }})</span>
                                        </FieldLabel>
                                        <Input v-model.number="form.quantity" type="number" min="0.0001"
                                            :max="product.pos_qty" step="any" placeholder="0" />
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Selling Price:</FieldLabel>
                                        <Input v-model="form.selling_price" type="number" min="0" step="0.01"
                                            placeholder="0.00" />
                                    </Field>
                                    <Field class="col-span-12">
                                        <FieldLabel class="font-normal">Cost:</FieldLabel>
                                        <Input v-model.number="form.cost" type="number" min="0" step="0.01"
                                            placeholder="0.00" />
                                    </Field>
                                    <Field class="col-span-12 mt-1">
                                        <BaseButton type="button" transactionType="create" :loading="isProcessing"
                                            :disabled="isProcessing" @click="openFormDialog" class="w-full" />
                                    </Field>
                                </div>
                            </FieldGroup>
                        </template>
                    </BaseField>
                </div>

            </div>
        </div>

        <template #footer>
            <BaseButton type="button" @click="emit('form-closed')" transactionType="cancel" />
        </template>

        <BaseAlertDialog v-model:open="isFormDialogOpen" :loading="isProcessing" transaction-type="create"
            @cancel="isFormDialogOpen = false" @confirm="submitLot" />
    </FormCard>
</template>
