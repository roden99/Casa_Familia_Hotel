<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';

import InputError from '@/components/InputError.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch, nextTick } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';


import { CalendarDate, fromDate, getLocalTimeZone } from '@internationalized/date';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { normalizeDate, set } from '@vueuse/core';
import BaseCombobox from '@/components/ui/BaseCombobox.vue';
import { Field, FieldGroup, FieldLabel, FieldLegend, FieldSeparator, FieldSet } from '@/components/ui/field';
import BaseTab from '@/components/BaseTab.vue'
import BaseField from '@/components/BaseField.vue';
import Switch from '@/components/ui/switch/Switch.vue';
import Skeleton from '@/components/ui/skeleton/Skeleton.vue';




const props = defineProps({

    isProcessing: {
        type: Boolean,
        default: false,
    },

    cardTitle: {
        type: String,
        default: 'Form',
    },

    customer: {
        type: Object,
        default: null,
    },

    transactionType: {
        type: String,
        default: 'create',
    },

});

const confirmButtonText = computed(() => {
    if (props.transactionType === 'create') return 'Save';
    if (props.transactionType === 'update') return 'Update';
    if (props.transactionType === 'delete') return 'Deactivate';
    return 'Yes';
});

const handleAlertClose = () => {
    isDialogOpen.value = false;

    if (props.transactionType === 'delete') {
        emit('member-form-closed')
    }
};


const isFormValidated = () => {
    const hasName = form.first_name.toString().trim() && form.last_name.toString().trim();
    const hasCompany = form.company.toString().trim();

    if (form.is_drugstore) {
        if (!hasCompany) {
            toast.error('A company name is required for drugstore customers');
            return false;
        }
    } else {
        if (!hasName) {
            toast.error('First name and last name are required');
            return false;
        }
    }

    return true;
};


const openConfirmDialog = () => {

    form.clearErrors();
    if (!isFormValidated()) return false;
    isDialogOpen.value = true;
    return true;

};

const buttonVariants = computed(() => {

    return props.transactionType === 'create' ? 'default' : props.transactionType === 'update' ? 'default' : 'destructive';
});





const form = useForm({

    //Customer Information
    is_drugstore: props.customer?.is_drugstore === true || props.customer?.is_drugstore === 1 || props.customer?.is_drugstore === 'Yes',
    company: props.customer?.company || '',
    first_name: props.customer?.first_name || '',
    last_name: props.customer?.last_name || '',
    middle_name: props.customer?.middle_name || '',
    email: props.customer?.email || '',
    phone: props.customer?.phone || '',
    address: props.customer?.address || '',
    sales_account_id: props.customer?.sales_account_id ? Number(props.customer.sales_account_id) : null,
});

const selectedSalesAccount = ref(props.customer?.sales_account_id ? String(props.customer.sales_account_id) : null);
const salesAccountOptions = ref([]);
const isLoading = ref(true);

watch(selectedSalesAccount, (val) => {
    form.sales_account_id = val ? Number(val) : null;
});

async function loadSalesAccounts(searchQuery = '') {
    try {
        const res = await axios.get('/sales-accounts', {
            headers: { Accept: 'application/json' },
            params: { search: searchQuery },
        });
        salesAccountOptions.value = res.data.salesAccounts.map((a) => ({
            value: String(a.id),
            label: a.account_name,
        }));
    } catch (error) {
        console.error('Failed to fetch sales accounts:', error);
        toast.error('Failed to load sales accounts. Please try again.');
    }
}




const emit = defineEmits(['handleSubmit', 'member-form-closed']);


const handleSubmit = () => {
    try {

        emit('handleSubmit', form.data());
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
}


// Address fields
const selectedProvince = ref('');
const selectedCity = ref('');
const selectedBarangay = ref('');

const provinceOptions = ref([]);
const cityOptions = ref([]);
const barangayOptions = ref([]);



const isDialogOpen = ref(false);

onMounted(async () => {
    if (props.transactionType === 'delete') {
        isDialogOpen.value = true;
    }
    const initialAccount = selectedSalesAccount.value;
    selectedSalesAccount.value = null;
    isLoading.value = true;
    await loadSalesAccounts();
    isLoading.value = false;
    await nextTick();
    selectedSalesAccount.value = initialAccount;
});

</script>

<template>
    <FormCard :loading="isProcessing" size="lg">
        <form @submit.prevent="Submit" class="space-y-4 mt-4">
            <BaseField legend="Customer Information" description="Enter customer details">
                <template #fields>
                    <FieldGroup>
                        <div class="grid w-full grid-cols-12 gap-4">


                            <Field class="col-span-6">
                                <div class="flex items-center space-x-2">
                                    <Switch :modelValue="form.is_drugstore"
                                        @update:modelValue="val => form.is_drugstore = val" />
                                    <FieldLabel for="is_drugstore" class="font-normal cursor-pointer">
                                        Drugstore
                                    </FieldLabel>
                                </div>
                            </Field>


                            <Field class="col-span-12">
                                <Skeleton v-if="isLoading" class="h-4 w-28 mb-1" />
                                <FieldLabel v-else class="font-normal">Sales Account:</FieldLabel>
                                <Skeleton v-if="isLoading" class="h-9 w-full" />
                                <BaseCombobox v-else v-model="selectedSalesAccount" :options="salesAccountOptions"
                                    empty-message="No sales account found" width="w-full" @search="loadSalesAccounts"
                                    placeholder="Select Sales Account" />
                            </Field>



                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Company:</FieldLabel>
                                <Input v-model="form.company" placeholder="Company Name" />
                            </Field>
                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Customer Name:</FieldLabel>
                                <div class="grid grid-cols-3 gap-4">
                                    <Input v-model="form.last_name" placeholder="Last Name" />
                                    <Input v-model="form.first_name" placeholder="First Name" />
                                    <Input v-model="form.middle_name" placeholder="Middle Name" />
                                </div>
                            </Field>

                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Phone Number:</FieldLabel>
                                <Input v-model="form.phone" placeholder="Phone Number" />
                            </Field>
                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Email Address:</FieldLabel>
                                <Input v-model="form.email" type="email" placeholder="Email Address" />
                            </Field>
                            <!-- <Field class="col-span-12">
                                <FieldLabel class="font-normal">Address:</FieldLabel>
                                <div class="grid grid-cols-3 gap-4">
                                    <BaseCombobox v-model="selectedProvince" placeholder="Province"
                                        empty-message="No province found" width="w-full" />
                                    <BaseCombobox v-model="selectedCity" placeholder="Municipality"
                                        empty-message="No municipality found" width="w-full" />
                                    <BaseCombobox v-model="selectedBarangay" placeholder="Select Barangay"
                                        empty-message="No barangay found" width="w-full" />
                                </div>
                            </Field> -->
                            <Field class="col-span-12 mb-6">
                                <FieldLabel class="font-normal">Customer Address:</FieldLabel>
                                <Input v-model="form.address" placeholder="Customer Address" />
                            </Field>
                        </div>
                    </FieldGroup>
                </template>
            </BaseField>
        </form>
        <template #footer>
            <BaseButton type="button" :disabled="isProcessing" @click="emit('member-form-closed')"
                transactionType="cancel">
            </BaseButton>
            <BaseButton type="button" @click="openConfirmDialog" :transactionType="props.transactionType"
                :loading="isProcessing" :disabled="isProcessing">
            </BaseButton>
        </template>
        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" :transaction-type="props.transactionType"
            @cancel="handleAlertClose" @confirm="handleSubmit" />
    </FormCard>
</template>