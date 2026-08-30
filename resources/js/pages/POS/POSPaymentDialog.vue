<script setup>
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { X, Check, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    receipt: { type: Object, required: true },
    isProcessing: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'cancel']);

const tendered = ref('');

const fmt = (val) =>
    Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const grandTotal = computed(() =>
    props.receipt.items.reduce((sum, item) => {
        const disc = Number(item.discount_percentage) || 0;
        return sum + Number(item.quantity) * Number(item.unit_price) * (1 - disc / 100);
    }, 0)
);

const tenderedNum = computed(() => Number(tendered.value) || 0);
const change = computed(() => tenderedNum.value - grandTotal.value);
const isInsufficient = computed(() => tendered.value !== '' && tenderedNum.value < grandTotal.value);
const canConfirm = computed(() => tendered.value !== '' && tenderedNum.value >= grandTotal.value);

const confirm = () => {
    emit('confirm', { tendered: tenderedNum.value, change: change.value });
};
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-background rounded-xl shadow-2xl w-full max-w-xs p-6 flex flex-col gap-4">
                <h2 class="text-base font-semibold">Payment</h2>

                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Total Due</span>
                        <span class="font-bold text-lg">{{ fmt(grandTotal) }}</span>
                    </div>

                    <div class="space-y-1">
                        <label class="text-sm text-muted-foreground">Tendered Amount</label>
                        <Input v-model="tendered" type="number" min="0" step="0.01" placeholder="0.00"
                            class="text-right text-base font-semibold" autofocus
                            @keyup.enter="canConfirm && confirm()" />
                        <p v-if="isInsufficient" class="text-xs text-destructive">Insufficient amount</p>
                    </div>

                    <div class="flex justify-between text-sm pt-1 border-t">
                        <span class="text-muted-foreground">Change</span>
                        <span :class="['font-bold text-lg', isInsufficient ? 'text-destructive' : 'text-emerald-600']">
                            {{ tendered !== '' ? fmt(Math.max(change, 0)) : '—' }}
                        </span>
                    </div>
                </div>

                <div class="flex gap-2 justify-end pt-1">
                    <Button variant="outline" :disabled="isProcessing" @click="emit('cancel')">
                        <X class="h-4 w-4 mr-1" /> Cancel
                    </Button>
                    <Button :disabled="!canConfirm || isProcessing" @click="confirm">
                        <Loader2 v-if="isProcessing" class="h-4 w-4 mr-1 animate-spin" />
                        <Check v-else class="h-4 w-4 mr-1" />
                        {{ isProcessing ? 'Saving...' : 'Confirm' }}
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
