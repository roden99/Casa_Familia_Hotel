<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';

import InputError from '@/components/InputError.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed, version } from 'vue';
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
    if (!form.first_name.toString().trim() ||
        !form.last_name.toString().trim() ||
        !form.phone.toString().trim() ||
        !form.email.toString().trim()) {
        toast.error('Fill up the forms properly');
        return false;
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
    first_name: props.customer?.first_name || '',
    last_name: props.customer?.last_name || '',
    middle_name: props.customer?.middle_name || '',
    email: props.customer?.email || '',
    phone: props.customer?.phone || '',
    address: props.customer?.address || '',
});




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

onMounted(() => {



    if (props.transactionType === 'delete') {
        isDialogOpen.value = true;
    }

});

</script>

<template>
    <FormCard :loading="isProcessing" size="lg">
        <form @submit.prevent="Submit" class="space-y-4 mt-4">
            <BaseField legend="Customer Information" description="Enter customer details">
                <template #fields>
                    <FieldGroup>
                        <div class="grid w-full grid-cols-12 gap-4">
                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Customer Name:</FieldLabel>
                                <div class="grid grid-cols-3 gap-4">
                                    <Input v-model="form.last_name" placeholder="Last Name" required />
                                    <Input v-model="form.first_name" placeholder="First Name" required />
                                    <Input v-model="form.middle_name" placeholder="Middle Name" />
                                </div>
                            </Field>
                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Phone Number:</FieldLabel>
                                <Input v-model="form.phone" required />
                            </Field>
                            <Field class="col-span-6">
                                <FieldLabel class="font-normal">Email Address:</FieldLabel>
                                <Input v-model="form.email" type="email" required />
                            </Field>
                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Address:</FieldLabel>
                                <div class="grid grid-cols-3 gap-4">
                                    <BaseCombobox v-model="selectedProvince" placeholder="Province"
                                        empty-message="No province found" width="w-full" />
                                    <BaseCombobox v-model="selectedCity" placeholder="Municipality"
                                        empty-message="No municipality found" width="w-full" />
                                    <BaseCombobox v-model="selectedBarangay" placeholder="Select Barangay"
                                        empty-message="No barangay found" width="w-full" />
                                </div>
                            </Field>
                            <Field class="col-span-12 mb-6">
                                <FieldLabel class="font-normal">Street Address / Unit / Building:</FieldLabel>
                                <Input v-model="form.address"
                                    placeholder="Enter street address, unit number, building name, etc." />
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