<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';

import InputError from '@/components/InputError.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed, version, watch } from 'vue';
import { toast } from 'vue-sonner';

import axios from 'axios';


import { CalendarDate, fromDate, getLocalTimeZone } from '@internationalized/date';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { normalizeDate, set } from '@vueuse/core';
import { Field, FieldGroup, FieldLabel, FieldLegend, FieldSeparator, FieldSet } from '@/components/ui/field';
import BaseTab from '@/components/BaseTab.vue'
import Switch from '@/components/ui/switch/Switch.vue';
import BaseField from '@/components/BaseField.vue';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import Skeleton from '@/components/ui/skeleton/Skeleton.vue';
import CreateBrand from '@/pages/Brands/CreateBrand.vue';
import CreateProductUnit from '@/pages/ProductUnits/CreateProductUnit.vue';
import CreateStrength from '@/pages/Strengths/CreateStrength.vue';
import CreateDrugForm from '@/pages/DrugForms/CreateDrugForm.vue';




const props = defineProps({

    isProcessing: {
        type: Boolean,
        default: false,
    },

    cardTitle: {
        type: String,
        default: 'Form',
    },

    product: {
        type: Object,
        default: null,
    },

    brands: {
        type: Array,
        default: () => []
    },

    productUnits: {
        type: Array,
        default: () => []
    },

    strengths: {
        type: Array,
        default: () => []
    },

    drugforms: {
        type: Array,
        default: () => []
    },

    transactionType: {
        type: String,
        default: 'create',
    },

});





const isDialogOpen = ref(false);

const emit = defineEmits(['handleSubmit', 'form-closed']);

const handleAlertClose = () => {
    isDialogOpen.value = false;

    if (props.transactionType === 'delete') {
        emit('form-closed');
    }
};

const openConfirmDialog = () => {
    form.clearErrors();
    isDialogOpen.value = true;
    return true;
};

watch(() => props.isProcessing, (newVal, oldVal) => {
    if (oldVal === true && newVal === false) {
        isDialogOpen.value = false;
    }
});


const isLoading = ref(true);

onMounted(async () => {
    if (props.transactionType === 'delete') {
        isDialogOpen.value = true;
    }
    isLoading.value = true;
    await Promise.all([
        loadBrandTypes(),
        loadUnitTypes(),
        loadStrengths(),
        loadDrugForms(),
    ]);
    isLoading.value = false;
});


const handleSubmit = () => {
    try {
        emit('handleSubmit', form.data());
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
}



const form = useForm({
    productname: props.product?.productname || '',
    brand_id: props.product?.brand_id || null,
    product_unit_id: props.product?.product_unit_id || null,
    strength_id: props.product?.strength_id || null,
    drugform_id: props.product?.drugform_id || null,
    isgeneric: props.product?.isgeneric || false,
});

const selectedBrand = ref(props.product?.brand_id ? String(props.product.brand_id) : null);
const selectedUnit = ref(props.product?.product_unit_id ? String(props.product.product_unit_id) : null);
const selectedStrength = ref(props.product?.strength_id ? String(props.product.strength_id) : null);
const selectedDrugForm = ref(props.product?.drugform_id ? String(props.product.drugform_id) : null);

watch(selectedBrand, (val) => {
    form.brand_id = val ? Number(val) : null;
});
watch(selectedUnit, (val) => {
    form.product_unit_id = val ? Number(val) : null;
});
watch(selectedStrength, (val) => {
    form.strength_id = val ? Number(val) : null;
});
watch(selectedDrugForm, (val) => {
    form.drugform_id = val ? Number(val) : null;
});





const brandTypes = ref([]);
const brandTypesOptions = ref([]);


async function loadBrandTypes(searchQuery = '') {
    try {
        const res = await axios.get('/brands', {
            headers: { Accept: 'application/json' },
            params: { search: searchQuery },
        });

        brandTypes.value = res.data.brands;
        brandTypesOptions.value = brandTypes.value.map((type) => ({
            value: String(type.id),
            label: type.brandname,
        }));

    } catch (error) {
        console.error('Failed to fetch brand types:', error);
        toast.error('Failed to load brand types. Please try again.');
    }
}

const unitTypes = ref([]);
const unitTypesOptions = ref([]);


async function loadUnitTypes(searchQuery = '') {
    try {
        const res = await axios.get('/product-units', {
            headers: { Accept: 'application/json' },
            params: { search: searchQuery },
        });

        unitTypes.value = res.data.productUnits;
        unitTypesOptions.value = unitTypes.value.map((type) => ({
            value: String(type.id),
            label: type.unit_name,
        }));

    } catch (error) {
        console.error('Failed to fetch unit types:', error);
        toast.error('Failed to load unit types. Please try again.');
    }
}


const strengthTypes = ref([]);
const strengthTypesOptions = ref([]);
async function loadStrengths(searchQuery = '') {
    try {
        const res = await axios.get('/strengths', {
            headers: { Accept: 'application/json' },
            params: { search: searchQuery },
        });

        strengthTypes.value = res.data.strengths;
        strengthTypesOptions.value = strengthTypes.value.map((type) => ({
            value: String(type.id),
            label: type.strengthname,
        }));

    } catch (error) {
        console.error('Failed to fetch strength types:', error);
        toast.error('Failed to load strength types. Please try again.');
    }
}



const drugForms = ref([]);
const drugFormsOptions = ref([]);
async function loadDrugForms(searchQuery = '') {
    try {
        const res = await axios.get('/drugforms', {
            headers: { Accept: 'application/json' },
            params: { search: searchQuery },
        });

        drugForms.value = res.data.drugforms;
        drugFormsOptions.value = drugForms.value.map((type) => ({
            value: String(type.id),
            label: type.drugformname,
        }));

    } catch (error) {
        console.error('Failed to fetch form types:', error);
        toast.error('Failed to load form types. Please try again.');
    }
}




</script>

<template>
    <FormCard :loading="isProcessing" size="lg">
        <form @submit.prevent="Submit" class="space-y-4 mt-4">
            <BaseField legend="Product Information" description="Enter product details">
                <template #fields>
                    <FieldGroup>
                        <div class="grid w-full grid-cols-12 gap-4">
                            <Field class="col-span-12">
                                <div class="flex items-center space-x-2">
                                    <Skeleton v-if="isLoading" class="h-5 w-9 rounded-full" />
                                    <Switch v-else @update:modelValue="val => form.isgeneric = val" />
                                    <Skeleton v-if="isLoading" class="h-4 w-14" />
                                    <FieldLabel v-else for="isgeneric" class="font-normal cursor-pointer">
                                        Generic
                                    </FieldLabel>
                                </div>
                            </Field>



                            <Field class="col-span-6">
                                <Skeleton v-if="isLoading" class="h-4 w-10 mb-1" />
                                <FieldLabel v-else class="font-normal">Brand:</FieldLabel>
                                <BaseCombobox v-model="selectedBrand" :options="brandTypesOptions"
                                    empty-message="Empty Search" width="w-full" @search="loadBrandTypes"
                                    :skeleton="isLoading">
                                    <template #create="{ close }">
                                        <CreateBrand
                                            @brand-created="(brand) => { brandTypesOptions.push({ value: String(brand.id), label: brand.brandname }); selectedBrand = String(brand.id); }"
                                            @form-closed="close" />
                                    </template>
                                </BaseCombobox>
                            </Field>

                            <Field class="col-span-6">
                                <Skeleton v-if="isLoading" class="h-4 w-8 mb-1" />
                                <FieldLabel v-else class="font-normal">Unit:</FieldLabel>
                                <BaseCombobox v-model="selectedUnit" :options="unitTypesOptions"
                                    empty-message="Empty Search" width="w-full" @search="loadUnitTypes"
                                    :skeleton="isLoading">
                                    <template #create="{ close }">
                                        <CreateProductUnit
                                            @unit-created="(unit) => { unitTypesOptions.push({ value: String(unit.id), label: unit.unit_name }); selectedUnit = String(unit.id); }"
                                            @form-closed="close" />
                                    </template>
                                </BaseCombobox>
                            </Field>

                            <Field class="col-span-6">
                                <Skeleton v-if="isLoading" class="h-4 w-16 mb-1" />
                                <FieldLabel v-else class="font-normal">Strength:</FieldLabel>
                                <BaseCombobox v-model="selectedStrength" :options="strengthTypesOptions"
                                    empty-message="Empty Search" width="w-full" @search="loadStrengths"
                                    :skeleton="isLoading">
                                    <template #create="{ close }">
                                        <CreateStrength
                                            @strength-created="(strength) => { strengthTypesOptions.push({ value: String(strength.id), label: strength.strengthname }); selectedStrength = String(strength.id); }"
                                            @form-closed="close" />
                                    </template>
                                </BaseCombobox>
                            </Field>

                            <Field class="col-span-6">
                                <Skeleton v-if="isLoading" class="h-4 w-10 mb-1" />
                                <FieldLabel v-else class="font-normal">Form:</FieldLabel>
                                <BaseCombobox v-model="selectedDrugForm" :options="drugFormsOptions"
                                    empty-message="Empty Search" width="w-full" @search="loadDrugForms"
                                    :skeleton="isLoading">
                                    <template #create="{ close }">
                                        <CreateDrugForm
                                            @drugform-created="(drugform) => { drugFormsOptions.push({ value: String(drugform.id), label: drugform.drugformname }); selectedDrugForm = String(drugform.id); }"
                                            @form-closed="close" />
                                    </template>
                                </BaseCombobox>
                            </Field>



                            <Field class="col-span-12">
                                <Skeleton v-if="isLoading" class="h-4 w-24 mb-1" />
                                <FieldLabel v-else class="font-normal">Product Name:</FieldLabel>
                                <Skeleton v-if="isLoading" class="h-9 w-full" />
                                <Input v-else v-model="form.productname" required />
                            </Field>

                        </div>
                    </FieldGroup>
                </template>
            </BaseField>
        </form>
        <template #footer>
            <Skeleton v-if="isLoading" class="h-9 w-20" />
            <BaseButton v-else type="button" :disabled="isProcessing" @click="emit('form-closed')"
                transactionType="cancel">
            </BaseButton>
            <Skeleton v-if="isLoading" class="h-9 w-20" />
            <BaseButton v-else type="button" @click="openConfirmDialog" :transactionType="props.transactionType"
                :loading="isProcessing" :disabled="isProcessing">
            </BaseButton>
        </template>
        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" :transaction-type="props.transactionType"
            @cancel="handleAlertClose" @confirm="handleSubmit" />
    </FormCard>
</template>
