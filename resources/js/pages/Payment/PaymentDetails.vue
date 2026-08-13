<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { toast } from 'vue-sonner';
import FormCard from '@/components/FormCard.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';

const props = defineProps({
    payment: { type: Object, required: true },
});

const emit = defineEmits(['form-closed']);

const isLoading = ref(true);
const details = ref(null);

onMounted(async () => {
    try {
        const res = await axios.get(`/payments/${props.payment.id}/details`, {
            params: { type: props.payment.type },
            headers: { Accept: 'application/json' },
        });
        details.value = res.data;
    } catch {
        toast.error('Failed to load payment details.');
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <FormCard size="2xl" cardTitle="Payment Details" :loading="isLoading" @close="emit('form-closed')">
        <div v-if="!isLoading && details" class="space-y-5 mt-4">

            <!-- Payment info header -->
            <div class="rounded-lg border bg-muted/40 px-5 py-4">
                <p class="text-base font-semibold mb-3">{{ details.payment.customer_name }}</p>
                <div class="flex flex-wrap gap-6 text-sm text-muted-foreground">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs uppercase tracking-wide font-medium">Account</span>
                        <span class="text-foreground font-semibold">{{ details.payment.account_name }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs uppercase tracking-wide font-medium">Date</span>
                        <span class="text-foreground font-semibold">{{ details.payment.payment_date }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs uppercase tracking-wide font-medium">Method</span>
                        <span class="text-foreground font-semibold">{{ details.payment.payment_method }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs uppercase tracking-wide font-medium">Amount</span>
                        <span class="text-foreground font-bold font-mono text-lg">{{ details.payment.amount }}</span>
                    </div>
                    <div v-if="details.payment.reference_no" class="flex flex-col gap-0.5">
                        <span class="text-xs uppercase tracking-wide font-medium">Reference</span>
                        <span class="text-foreground font-semibold">{{ details.payment.reference_no }}</span>
                    </div>
                    <div v-if="details.payment.check_number" class="flex flex-col gap-0.5">
                        <span class="text-xs uppercase tracking-wide font-medium">Check No.</span>
                        <span class="text-foreground font-semibold">{{ details.payment.check_number }}</span>
                    </div>
                    <div v-if="details.payment.check_date" class="flex flex-col gap-0.5">
                        <span class="text-xs uppercase tracking-wide font-medium">Check Date</span>
                        <span class="text-foreground font-semibold">{{ details.payment.check_date }}</span>
                    </div>
                </div>
                <p v-if="details.payment.notes" class="mt-3 text-sm text-muted-foreground italic">
                    {{ details.payment.notes }}
                </p>
            </div>

            <!-- Applied to table -->
            <div v-if="details.applied.length === 0"
                class="flex items-center justify-center py-10 text-muted-foreground text-sm">
                No records linked to this payment.
            </div>

            <div v-else class="rounded-md border overflow-hidden">
                <div class="overflow-y-auto max-h-[40vh]">
                    <Table>
                        <TableHeader class="sticky top-0 z-10 bg-background">
                            <TableRow class="bg-muted/60 hover:bg-muted/60">
                                <TableHead class="font-semibold">Applied To</TableHead>
                                <TableHead class="font-semibold">Date</TableHead>
                                <TableHead class="text-right font-semibold">Amount</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(item, i) in details.applied" :key="i">
                                <TableCell class="text-sm">{{ item.label }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ item.date }}</TableCell>
                                <TableCell class="text-right font-mono text-sm font-medium">{{ item.amount }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

        </div>

        <template #footer>
            <button
                class="ml-auto inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm hover:bg-accent hover:text-accent-foreground"
                @click="emit('form-closed')">
                Close
            </button>
        </template>
    </FormCard>
</template>
