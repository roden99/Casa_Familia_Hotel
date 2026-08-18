<script setup>
import { ref, onMounted } from 'vue';
import { X, TrendingUp } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import axios from 'axios';

const props = defineProps({
    product:  { type: Object, required: true },
    endpoint: { type: String, default: null },
});

const emit = defineEmits(['form-closed']);

const isLoading = ref(true);
const productInfo = ref(null);
const rows = ref([]);

const fmt = (n) => Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2 });

onMounted(async () => {
    const url = props.endpoint ?? `/store-inventory/${props.product.id}/pricing-history`;
    try {
        const res = await axios.get(url);
        productInfo.value = res.data.product;
        rows.value = res.data.rows;
    } catch {
        toast.error('Failed to load pricing history.');
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="emit('form-closed')" />

        <div class="relative z-10 w-full max-w-4xl rounded-2xl border bg-background shadow-2xl overflow-hidden">

            <!-- Header -->
            <div class="flex items-center justify-between border-b px-6 py-4 bg-muted/30">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-violet-100 dark:bg-violet-900/40">
                        <TrendingUp class="h-4.5 w-4.5 text-violet-600 dark:text-violet-400" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold leading-tight">Pricing History</p>
                        <p class="text-xs text-muted-foreground truncate max-w-xs">{{ productInfo?.display_name ?? '…' }}</p>
                    </div>
                </div>
                <button @click="emit('form-closed')"
                    class="rounded-md p-1.5 text-muted-foreground hover:bg-muted transition-colors">
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Body -->
            <div class="px-6 py-5 max-h-[65vh] overflow-y-auto">
                <div v-if="isLoading" class="flex items-center justify-center py-16 text-muted-foreground text-sm gap-2">
                    Loading…
                </div>

                <div v-else-if="rows.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-muted-foreground gap-2">
                    <TrendingUp class="h-8 w-8 opacity-30" />
                    <p class="text-sm">No delivery records found for this product.</p>
                </div>

                <table v-else class="w-full text-sm">
                    <thead class="sticky top-0 bg-background">
                        <tr class="border-b text-xs text-muted-foreground uppercase tracking-wide">
                            <th class="pb-2 text-left font-medium">Date</th>
                            <th class="pb-2 text-left font-medium">Reference</th>
                            <th class="pb-2 text-left font-medium">Supplier</th>
                            <th class="pb-2 text-right font-medium">Qty</th>
                            <th class="pb-2 text-right font-medium">Unit Cost</th>
                            <th class="pb-2 text-right font-medium">Selling Price</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="(row, i) in rows" :key="i" class="hover:bg-muted/30">
                            <td class="py-2 text-muted-foreground">{{ row.date }}</td>
                            <td class="py-2 font-mono text-xs">{{ row.reference }}</td>
                            <td class="py-2 truncate max-w-[180px]">{{ row.supplier }}</td>
                            <td class="py-2 text-right font-semibold">{{ row.quantity }}</td>
                            <td class="py-2 text-right font-mono">{{ row.unit_price }}</td>
                            <td class="py-2 text-right font-mono text-emerald-700 dark:text-emerald-400 font-semibold">{{ row.selling_price }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            <div class="flex justify-end border-t px-6 py-4 bg-muted/20">
                <button @click="emit('form-closed')"
                    class="rounded-md px-4 py-2 text-sm font-medium bg-muted hover:bg-muted/80 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
</template>
