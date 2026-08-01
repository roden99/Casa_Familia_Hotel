<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';

const props = defineProps({
    isProcessing: { type: Boolean, default: false },
    product: { type: Object, default: null },
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const form = useForm({
    pos_selling_price: props.product?.pos_selling_price ?? 0,
});

const isLoading = ref(true);
const isDialogOpen = ref(false);
const isBusy = computed(() => props.isProcessing);

const handleAlertClose = () => {
    isDialogOpen.value = false;
    emit('form-closed');
};

const openConfirmDialog = () => {
    form.clearErrors();
    if (form.pos_selling_price === '' || form.pos_selling_price < 0) {
        toast.error('Please enter a valid selling price.');
        return;
    }
    isDialogOpen.value = true;
};

const handleSubmit = () => {
    try {
        emit('handleSubmit', form.data());
    } catch (error) {
        toast.error('ERROR', { description: error.message });
    }
};

onMounted(() => { isLoading.value = false; });
</script>

<template>
    <FormCard :loading="isProcessing" size="md">
        <form @submit.prevent class="space-y-4 mt-4">
            <BaseField legend="Selling Price" description="Set the POS selling price for this product">
                <template #fields>
                    <FieldGroup :skeleton="isLoading" :skeleton-rows="1" :skeleton-cols="1">
                        <div class="grid w-full grid-cols-12 gap-4">
                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Product:</FieldLabel>
                                <span class="text-sm font-medium">{{ product?.display_name ?? product?.productname ??
                                    '—' }}</span>
                            </Field>
                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Selling Price:</FieldLabel>
                                <Input v-model.number="form.pos_selling_price" type="number" min="0" step="0.01"
                                    placeholder="0.00" required />
                            </Field>
                        </div>
                    </FieldGroup>
                </template>
            </BaseField>
        </form>

        <template #footer>
            <BaseButton type="button" :disabled="isBusy" @click="emit('form-closed')" transactionType="cancel"
                :skeleton="isLoading" />
            <BaseButton type="button" :disabled="isBusy" @click="openConfirmDialog" transactionType="update"
                :loading="isProcessing" :skeleton="isLoading" />
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" transaction-type="update"
            @cancel="handleAlertClose" @confirm="handleSubmit" />
    </FormCard>
</template>
