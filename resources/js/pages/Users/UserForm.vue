<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseAlertDialog from '@/components/ui/BaseAlertDialog.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import BaseField from '@/components/BaseField.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';

const props = defineProps({
    isProcessing: { type: Boolean, default: false },
    user:         { type: Object, default: null },
    transactionType: { type: String, default: 'create' },
});

const emit = defineEmits(['handleSubmit', 'form-closed']);

const form = useForm({
    name:     props.user?.name     || '',
    email:    props.user?.email    || '',
    password: '',
    role:     props.user?.role     || 'pos',
});

const isDialogOpen = ref(false);
const isBusy = computed(() => props.isProcessing);

const roleOptions = [
    { value: 'admin', label: 'Admin' },
    { value: 'pos',   label: 'POS User' },
];

const openConfirmDialog = () => {
    if (!form.name.trim())  { toast.error('Name is required.');  return; }
    if (!form.email.trim()) { toast.error('Email is required.'); return; }
    if (props.transactionType === 'create' && !form.password.trim()) {
        toast.error('Password is required.'); return;
    }
    isDialogOpen.value = true;
};

const handleSubmit = () => emit('handleSubmit', form.data());
</script>

<template>
    <FormCard :loading="false" size="md">
        <form @submit.prevent class="space-y-4 mt-4">
            <BaseField :legend="transactionType === 'create' ? 'New User' : 'Edit User'"
                description="User account details and access level">
                <template #fields>
                    <FieldGroup>
                        <div class="grid w-full grid-cols-12 gap-4">
                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Name: <span class="text-destructive">*</span></FieldLabel>
                                <Input v-model="form.name" placeholder="Full name" />
                            </Field>
                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Email: <span class="text-destructive">*</span></FieldLabel>
                                <Input v-model="form.email" type="email" placeholder="email@example.com" />
                            </Field>
                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">
                                    Password:
                                    <span v-if="transactionType === 'create'" class="text-destructive">*</span>
                                    <span v-else class="text-muted-foreground text-xs ml-1">(leave blank to keep current)</span>
                                </FieldLabel>
                                <Input v-model="form.password" type="password" placeholder="Min. 8 characters" />
                            </Field>
                            <Field class="col-span-12">
                                <FieldLabel class="font-normal">Role: <span class="text-destructive">*</span></FieldLabel>
                                <BaseSelect v-model="form.role" :options="roleOptions" />
                            </Field>
                        </div>
                    </FieldGroup>
                </template>
            </BaseField>
        </form>

        <template #footer>
            <BaseButton type="button" @click="emit('form-closed')" transactionType="cancel" />
            <BaseButton type="button" :transactionType="transactionType" :loading="isBusy" :disabled="isBusy"
                @click="openConfirmDialog" />
        </template>

        <BaseAlertDialog v-model:open="isDialogOpen" :loading="isProcessing" :transaction-type="transactionType"
            @cancel="isDialogOpen = false" @confirm="handleSubmit" />
    </FormCard>
</template>
