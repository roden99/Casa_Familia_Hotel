<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import InputError from '@/components/InputError.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Field, FieldGroup, FieldLabel, FieldSet, FieldSeparator } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';
import BaseDatePick from '@/components/ui/BaseDatePick.vue';
import Skeleton from '@/components/ui/skeleton/Skeleton.vue';
import axios from 'axios';
import CreateSupplier from '../Suppliers/CreateSupplier.vue';

import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

import BaseCombobox from '@/components/ui/BaseCombobox.vue';

const invoices = [
    {
        invoice: 'INV001',
        paymentStatus: 'Paid',
        totalAmount: '$250.00',
        paymentMethod: 'Credit Card',
    },
    {
        invoice: 'INV002',
        paymentStatus: 'Pending',
        totalAmount: '$150.00',
        paymentMethod: 'PayPal',
    },
    {
        invoice: 'INV003',
        paymentStatus: 'Unpaid',
        totalAmount: '$350.00',
        paymentMethod: 'Bank Transfer',
    },
    {
        invoice: 'INV004',
        paymentStatus: 'Paid',
        totalAmount: '$450.00',
        paymentMethod: 'Credit Card',
    },
    {
        invoice: 'INV005',
        paymentStatus: 'Paid',
        totalAmount: '$550.00',
        paymentMethod: 'PayPal',
    },
    {
        invoice: 'INV006',
        paymentStatus: 'Pending',
        totalAmount: '$200.00',
        paymentMethod: 'Bank Transfer',
    },
    {
        invoice: 'INV007',
        paymentStatus: 'Unpaid',
        totalAmount: '$300.00',
        paymentMethod: 'Credit Card',
    },
]

const props = defineProps({
    isProcessing: {
        type: Boolean,
        default: false,
    },

    cardTitle: {
        type: String,
        default: 'Form',
    },

    delivery: {
        type: Object,
        default: null,
    },

    suppliers: {
        type: Array,
        default: () => []
    },

    transactionType: {
        type: String,
        default: 'create',
    },
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const isDialogOpen = ref(false);
const isLoading = ref(false);

const handleAlertClose = () => {
    isDialogOpen.value = false;
    if (props.transactionType === 'delete') {
        emit('form-closed');
    }
};

const openConfirmDialog = () => {
    isDialogOpen.value = true;
};

watch(() => props.isProcessing, (newVal, oldVal) => {
    if (oldVal === true && newVal === false) {
        isDialogOpen.value = false;
    }
});

const handleSubmit = () => {
    try {
        emit('handleSubmit', {});
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
};

onMounted(async () => {
    if (props.transactionType === 'delete') {
        isDialogOpen.value = true;
    }
    isLoading.value = true;
    await Promise.all([
        loadSuppliers(),
    ]);
    isLoading.value = false;
});





const suppliers = ref([]);
const supplierOptions = ref([]);


async function loadSuppliers(searchQuery = '') {
    // isLoading.value = true;
    try {
        const res = await axios.get('/suppliers', {
            headers: { Accept: 'application/json' },
            params: { search: searchQuery },
        });
        const supplierArr = Array.isArray(res.data.suppliers) ? res.data.suppliers : [];
        suppliers.value = supplierArr;
        supplierOptions.value = supplierArr.map((supplier) => ({
            value: String(supplier.id),
            label: supplier.company,
        }));
    } catch (error) {
        console.error('Failed to fetch suppliers:', error);
        toast.error('Failed to load suppliers. Please try again.');
    } finally {
        // isLoading.value = false;
    }
}




</script>

<template>
    <FormCard :loading="false" size="lg">
        <form @submit.prevent="Submit" class="space-y-4 mt-4">
            <BaseField legend="Delivery Information" description="Enter delivery details">
                <template #fields>
                    <FieldGroup>


                        <div class="grid w-full grid-cols-12 gap-4">


                            <Field class="col-span-6">
                                <Skeleton v-if="isLoading" class="h-4 w-10 mb-1" />
                                <FieldLabel v-else class="font-normal">Supplier:</FieldLabel>
                                <BaseCombobox v-model="selectedSupplier" :options="supplierOptions"
                                    empty-message="Empty Search" width="w-full" @search="loadSuppliers"
                                    :skeleton="isLoading">
                                    <template #create="{ close }">
                                        <CreateSupplier
                                            @supplier-created="(supplier) => { supplierOptions.push({ value: String(supplier.id), label: supplier.company }); selectedSupplier = String(supplier.id); }"
                                            @form-closed="close" />
                                    </template>
                                </BaseCombobox>
                            </Field>




                            <Field class="col-span-3">
                                <FieldLabel class="font-normal">Delivery No.:</FieldLabel>
                                <Input />
                            </Field>

                            <Field class="col-span-3">
                                <FieldLabel class="font-normal">Terms:</FieldLabel>
                                <Input />
                            </Field>


                        </div>

                        <div class="grid w-full grid-cols-12 gap-4">

                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Invoice Date:</FieldLabel>
                                <BaseDatePick class="w-32" />
                            </Field>


                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Received Date:</FieldLabel>
                                <BaseDatePick class="w-32" />
                            </Field>
                        </div>

                        <FieldSeparator />

                    </FieldGroup>


                    <FieldGroup>
                        <div class="grid w-full grid-cols-12 gap-4">
                            <Field class="col-span-12">
                                <SearchableCombobox v-model="selectedBrand" :initial-options="brandsWithCurrent"
                                    endpoint="/brands" response-key="brands" label-key="brandname"
                                    empty-message="Empty Search. Create?" width="w-full" :max-results="5" />
                            </Field>
                        </div>



                        <Table>
                            <TableCaption>List of your deliveries.</TableCaption>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-[100px]">Description</TableHead>
                                    <TableHead>Unit</TableHead>
                                    <TableHead>Quantity</TableHead>
                                    <TableHead class="text-right">Unit Price</TableHead>
                                    <TableHead class="text-right">Amount</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow v-for="invoice in invoices" :key="invoice.invoice">
                                    <TableCell>{{ invoice.invoice }}</TableCell>
                                    <TableCell>{{ invoice.paymentStatus }}</TableCell>
                                    <TableCell>{{ invoice.paymentMethod }}</TableCell>
                                    <TableCell class="text-right">{{ invoice.totalAmount }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </FieldGroup>
                </template>
            </BaseField>
        </form>

        <template #footer>
            <BaseButton type="button" :disabled="isProcessing" @click="emit('form-closed')" transactionType="cancel">
            </BaseButton>

            <BaseButton type="button" @click="openConfirmDialog" :transactionType="props.transactionType"
                :loading="isProcessing" :disabled="isProcessing">
            </BaseButton>
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" :transaction-type="props.transactionType"
            @cancel="handleAlertClose" @confirm="handleSubmit" />
    </FormCard>
</template>
