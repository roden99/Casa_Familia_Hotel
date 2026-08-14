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
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import Switch from '@/components/ui/switch/Switch.vue';
import Skeleton from '@/components/ui/skeleton/Skeleton.vue';
import { useFieldGroupSkeleton } from '@/composables/useFieldGroupSkeleton';
import CreateBrand from '@/pages/Brands/CreateBrand.vue';
import CreateProductUnit from '@/pages/ProductUnits/CreateProductUnit.vue';
import CreateProductType from '@/pages/ProductTypes/CreateProductType.vue';
import CreateDrugForm from '@/pages/DrugForms/CreateDrugForm.vue';
import axios from 'axios';

const props = defineProps({
    isProcessing: { type: Boolean, default: false },
    product: { type: Object, default: null },
    transactionType: { type: String, default: 'create' },
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const form = useForm({
    productname: props.product?.productname ?? '',
    product_type_id: props.product?.product_type_id ?? null,
    brand_id: props.product?.brand_id ?? null,
    drugform_id: props.product?.drugform_id ?? null,
    product_unit_id: props.product?.product_unit_id ?? null,
    isgeneric: props.product?.isgeneric ?? false,
});

const isLoading = ref(true);
const isDialogOpen = ref(false);
const isBusy = computed(() => isLoading.value || props.isProcessing);

const { skeletonLayout } = useFieldGroupSkeleton([12, 6, 6, 6, 6]);

const selectedProductType = ref(props.product?.product_type_id ? String(props.product.product_type_id) : null);
const selectedBrand = ref(props.product?.brand_id ? String(props.product.brand_id) : null);
const selectedDrugForm = ref(props.product?.drugform_id ? String(props.product.drugform_id) : null);
const selectedUnit = ref(props.product?.product_unit_id ? String(props.product.product_unit_id) : null);

watch(selectedProductType, val => { form.product_type_id = val ? Number(val) : null; });
watch(selectedBrand, val => { form.brand_id = val ? Number(val) : null; });
watch(selectedDrugForm, val => { form.drugform_id = val ? Number(val) : null; });
watch(selectedUnit, val => { form.product_unit_id = val ? Number(val) : null; });

const productTypesOptions = ref([]);
const brandTypesOptions = ref([]);
const drugFormsOptions = ref([]);
const unitTypesOptions = ref([]);

async function loadProductTypes(q = '', includeId = null) {
    try {
        const res = await axios.get('/product-types', { headers: { Accept: 'application/json' }, params: { search: q, include_id: includeId } });
        productTypesOptions.value = res.data.productTypes.map(t => ({ value: String(t.id), label: t.type_name }));
    } catch { toast.error('Failed to load product types.'); }
}

async function loadBrands(q = '', includeId = null) {
    try {
        const res = await axios.get('/brands', { headers: { Accept: 'application/json' }, params: { search: q, include_id: includeId } });
        brandTypesOptions.value = res.data.brands.map(b => ({ value: String(b.id), label: b.brandname }));
    } catch { toast.error('Failed to load brands.'); }
}

async function loadDrugForms(q = '', includeId = null) {
    try {
        const res = await axios.get('/drugforms', { headers: { Accept: 'application/json' }, params: { search: q, include_id: includeId } });
        drugFormsOptions.value = res.data.drugforms.map(d => ({ value: String(d.id), label: d.drugformname }));
    } catch { toast.error('Failed to load drug forms.'); }
}

async function loadUnits(q = '', includeId = null) {
    try {
        const res = await axios.get('/product-units', { headers: { Accept: 'application/json' }, params: { search: q, include_id: includeId } });
        unitTypesOptions.value = res.data.productUnits.map(u => ({ value: String(u.id), label: u.unit_name }));
    } catch { toast.error('Failed to load units.'); }
}

const openConfirmDialog = () => {
    if (props.transactionType !== 'delete') {
        if (!form.productname.trim()) { toast.error('Product name is required.'); return; }
        if (!form.product_unit_id) { toast.error('Unit is required.'); return; }
        if (!form.product_type_id) { toast.error('Type is required.'); return; }
    }
    isDialogOpen.value = true;
};

watch(() => props.isProcessing, (newVal, oldVal) => {
    if (oldVal === true && newVal === false) isDialogOpen.value = false;
});

const handleSubmit = () => {
    emit('handleSubmit', form.data());
};

onMounted(async () => {
    isLoading.value = true;
    await Promise.all([
        loadProductTypes('', props.product?.product_type_id),
        loadBrands('', props.product?.brand_id),
        loadDrugForms('', props.product?.drugform_id),
        loadUnits('', props.product?.product_unit_id),
    ]);
    isLoading.value = false;
    if (props.transactionType === 'delete') isDialogOpen.value = true;
});
</script>

<template>
    <FormCard size="lg" :loading="false">
        <form @submit.prevent class="space-y-4 mt-4">
            <BaseField
                :legend="transactionType === 'update' ? 'Update POS Item' : transactionType === 'delete' ? 'Delete POS Item' : 'New POS Item'"
                description="Product will not be tracked in main inventory">
                <template #fields>
                    <FieldGroup :skeleton="isLoading" :skeleton-layout="skeletonLayout">
                        <div class="grid w-full grid-cols-12 gap-4">

                            <Field class="col-span-12">
                                <div class="flex items-center space-x-2">
                                    <Switch :model-value="form.isgeneric"
                                        @update:modelValue="val => form.isgeneric = val" />
                                    <FieldLabel class="font-normal cursor-pointer">Generic</FieldLabel>
                                </div>
                            </Field>

                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Generic Name / Product Name: <span
                                        class="text-destructive">*</span></FieldLabel>
                                <Input v-model="form.productname" placeholder="Enter product name" />
                            </Field>

                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Type: <span class="text-destructive">*</span>
                                </FieldLabel>
                                <BaseCombobox v-model="selectedProductType" :options="productTypesOptions"
                                    empty-message="No types" width="w-full" placeholder="Select type..."
                                    @search="q => loadProductTypes(q, selectedProductType)">
                                    <template #create="{ close }">
                                        <CreateProductType
                                            @type-created="t => { productTypesOptions.push({ value: String(t.id), label: t.type_name }); selectedProductType = String(t.id); }"
                                            @form-closed="close" />
                                    </template>
                                </BaseCombobox>
                            </Field>

                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Brand:</FieldLabel>
                                <BaseCombobox v-model="selectedBrand" :options="brandTypesOptions"
                                    empty-message="No brands" width="w-full" placeholder="Select brand..."
                                    @search="q => loadBrands(q, selectedBrand)">
                                    <template #create="{ close }">
                                        <CreateBrand
                                            @brand-created="b => { brandTypesOptions.push({ value: String(b.id), label: b.brandname }); selectedBrand = String(b.id); }"
                                            @form-closed="close" />
                                    </template>
                                </BaseCombobox>
                            </Field>

                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Form:</FieldLabel>
                                <BaseCombobox v-model="selectedDrugForm" :options="drugFormsOptions"
                                    empty-message="No forms" width="w-full" placeholder="Select form..."
                                    @search="q => loadDrugForms(q, selectedDrugForm)">
                                    <template #create="{ close }">
                                        <CreateDrugForm
                                            @drugform-created="d => { drugFormsOptions.push({ value: String(d.id), label: d.drugformname }); selectedDrugForm = String(d.id); }"
                                            @form-closed="close" />
                                    </template>
                                </BaseCombobox>
                            </Field>

                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Unit: <span class="text-destructive">*</span>
                                </FieldLabel>
                                <BaseCombobox v-model="selectedUnit" :options="unitTypesOptions"
                                    empty-message="No units" width="w-full" placeholder="Select unit..."
                                    @search="q => loadUnits(q, selectedUnit)">
                                    <template #create="{ close }">
                                        <CreateProductUnit
                                            @unit-created="u => { unitTypesOptions.push({ value: String(u.id), label: u.unit_name }); selectedUnit = String(u.id); }"
                                            @form-closed="close" />
                                    </template>
                                </BaseCombobox>
                            </Field>

                        </div>
                        <FieldSeparator />
                    </FieldGroup>
                </template>
            </BaseField>
        </form>

        <template #footer>
            <Skeleton v-if="isLoading" class="h-9 w-20" />
            <BaseButton v-else type="button" @click="emit('form-closed')" transactionType="cancel" />
            <Skeleton v-if="isLoading" class="h-9 w-20" />
            <BaseButton v-else type="button"
                :transactionType="transactionType === 'update' ? 'update' : transactionType === 'delete' ? 'delete' : 'create'"
                :loading="isBusy" :disabled="isBusy" @click="openConfirmDialog" />
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :loading="props.isProcessing"
            :transaction-type="transactionType === 'update' ? 'update' : transactionType === 'delete' ? 'delete' : 'create'"
            @cancel="isDialogOpen = false" @confirm="handleSubmit" />
    </FormCard>
</template>
