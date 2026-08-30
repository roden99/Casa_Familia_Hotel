<script setup>
import FormCard from '@/components/FormCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const props = defineProps({
    product: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);
</script>

<template>
    <FormCard size="lg" :loading="false">
        <div class="space-y-4 mt-4">
            <div class="rounded-lg border bg-muted/40 px-5 py-4">
                <dl class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
                    <div>
                        <dt class="text-muted-foreground">Type</dt>
                        <dd class="font-medium">{{ product.generic_text }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Category</dt>
                        <dd class="font-medium">{{ product.type_name }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-muted-foreground">Product Name</dt>
                        <dd class="font-medium">{{ product.display_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Brand</dt>
                        <dd class="font-medium">{{ product.brand_name }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <template #footer>
            <BaseButton type="button" transactionType="cancel" @click="emit('form-closed')" />
        </template>
    </FormCard>
</template>
