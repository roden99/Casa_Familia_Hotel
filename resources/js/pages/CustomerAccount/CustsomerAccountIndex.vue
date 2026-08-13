<script setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import CustomerAccountCreate from './CustomerAccountCreate.vue';
import CustomerAccountLedger from './CustomerAccountLedger.vue';
import CustomerAccountPayment from './CustomerAccountPayment.vue';
import CustomerAccountInvoice from './CustomerAccountInvoice.vue';
import { ref, computed } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import { Hospital, User, Phone, MapPin, BookOpen, CreditCard, FilePlus, Search } from 'lucide-vue-next';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Customer Accounts', href: '/customer-accounts' },
];

const props = defineProps({
    customers: { required: true },
    columns: { type: Array, required: true },
    accounts: { type: Array, default: () => [] },
});

const currentAccount = ref(new URLSearchParams(window.location.search).get('account') || 'all');
const currentType = ref(new URLSearchParams(window.location.search).get('type') || 'all');
const searchText = ref('');

const customerList = computed(() =>
    Array.isArray(props.customers) ? props.customers : (props.customers?.data ?? [])
);

const filtered = computed(() => {
    const q = searchText.value.trim().toLowerCase();
    return customerList.value.filter(c => {
        if (!q) return true;
        return (
            (c.display_name ?? '').toLowerCase().includes(q) ||
            (c.account_name ?? '').toLowerCase().includes(q) ||
            (c.phone ?? '').toLowerCase().includes(q) ||
            (c.company ?? '').toLowerCase().includes(q)
        );
    });
});

const isDrugstore = (c) => c.is_drugstore === true || c.is_drugstore === 'YES' || c.is_drugstore === 1;

const balanceColor = (bal) => {
    const n = parseFloat((bal ?? '0').toString().replace(/,/g, ''));
    return n > 0
        ? 'text-destructive font-bold'
        : 'text-green-600 dark:text-green-400 font-bold';
};

const handleTypeFilter = (type) => {
    currentType.value = type;
    const url = new URL(window.location.href);
    type === 'all' ? url.searchParams.delete('type') : url.searchParams.set('type', type);
    url.searchParams.delete('page');
    router.get(url.pathname + url.search);
};

const handleAccountFilter = (value) => {
    currentAccount.value = value;
    const url = new URL(window.location.href);
    value === 'all' ? url.searchParams.delete('account') : url.searchParams.set('account', value);
    url.searchParams.delete('page');
    router.get(url.pathname + url.search);
};

const showCreateModal = ref(false);
const showLedgerModal = ref(false);
const showPaymentModal = ref(false);
const showInvoiceModal = ref(false);
const selectedAccount = ref(null);

const openLedger = (c) => { selectedAccount.value = c; showLedgerModal.value = true; };
const openPayment = (c) => { selectedAccount.value = c; showPaymentModal.value = true; };
const openInvoice = (c) => { selectedAccount.value = c; showInvoiceModal.value = true; };
</script>

<template>

    <Head title="Customer Accounts" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">

            <!-- Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <Button variant="default" @click="showCreateModal = true">New Account</Button>

                <div class="relative flex-1 min-w-48 max-w-64">
                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input v-model="searchText" placeholder="Search…" class="pl-8" />
                </div>

                <Select :model-value="currentAccount" @update:model-value="handleAccountFilter">
                    <SelectTrigger class="w-44">
                        <SelectValue placeholder="All Accounts" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Accounts</SelectItem>
                        <SelectItem v-for="a in props.accounts" :key="a.value" :value="a.value">
                            {{ a.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <div class="flex items-center gap-1 border rounded-md p-1">
                    <Button :variant="currentType === 'all' ? 'default' : 'ghost'" size="sm"
                        @click="handleTypeFilter('all')">All</Button>
                    <Button :variant="currentType === 'drugstore' ? 'default' : 'ghost'" size="sm" class="gap-1"
                        @click="handleTypeFilter('drugstore')">
                        <Hospital class="h-4 w-4" /> Drugstore
                    </Button>
                    <Button :variant="currentType === 'person' ? 'default' : 'ghost'" size="sm" class="gap-1"
                        @click="handleTypeFilter('person')">
                        <User class="h-4 w-4" /> Doctor
                    </Button>
                </div>

                <span class="text-xs text-muted-foreground ml-auto">{{ filtered.length }} record{{ filtered.length !== 1
                    ? 's' : '' }}</span>
            </div>

            <!-- Card grid -->
            <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 overflow-y-auto">
                <div v-if="filtered.length === 0" class="col-span-full text-center text-muted-foreground py-16 text-sm">
                    No accounts found.
                </div>

                <div v-for="c in filtered" :key="c.csa_id"
                    class="relative flex flex-col gap-3 rounded-xl border bg-card p-4 shadow-sm hover:shadow-md transition-shadow">
                    <!-- Type icon badge top-right (icon only) -->
                    <div :class="isDrugstore(c)
                        ? 'text-green-600 border-green-200 dark:border-green-800'
                        : 'text-blue-500 border-blue-200 dark:border-blue-800'"
                        class="absolute top-3 right-3 flex h-7 w-7 items-center justify-center rounded-full border bg-muted">
                        <Hospital v-if="isDrugstore(c)" class="h-3.5 w-3.5" />
                        <User v-else class="h-3.5 w-3.5" />
                    </div>

                    <!-- Account name → Customer name → Address -->
                    <div class="min-w-0 pr-10">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground truncate">{{
                            c.account_name }}</p>
                        <p class="text-sm font-bold leading-tight truncate mt-0.5">{{ c.display_name }}</p>
                        <div v-if="c.address" class="flex items-center gap-1 mt-1 text-xs text-muted-foreground">
                            <MapPin class="h-3 w-3 shrink-0" />
                            <span class="truncate">{{ c.address }}</span>
                        </div>
                    </div>

                    <!-- Balance -->
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-xs text-muted-foreground uppercase tracking-wide">Balance</span>
                        <span :class="balanceColor(c.balance)" class="font-mono text-sm">₱ {{ c.balance }}</span>
                    </div>

                    <!-- Contact -->
                    <div class="space-y-1 text-xs text-muted-foreground">
                        <div v-if="c.phone" class="flex items-center gap-2 truncate">
                            <Phone class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">{{ c.phone }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-auto flex gap-1 pt-1 border-t">
                        <button
                            class="flex flex-1 items-center justify-center gap-1 rounded-md px-2 py-1.5 text-xs font-medium text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
                            @click="openLedger(c)">
                            <BookOpen class="h-3.5 w-3.5" /> Ledger
                        </button>
                        <button
                            class="flex flex-1 items-center justify-center gap-1 rounded-md px-2 py-1.5 text-xs font-medium text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
                            @click="openPayment(c)">
                            <CreditCard class="h-3.5 w-3.5" /> Payment
                        </button>
                        <button
                            class="flex flex-1 items-center justify-center gap-1 rounded-md px-2 py-1.5 text-xs font-medium text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
                            @click="openInvoice(c)">
                            <FilePlus class="h-3.5 w-3.5" /> Invoice
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <CustomerAccountCreate v-if="showCreateModal" @member-form-closed="showCreateModal = false"
            @customer-account-created="() => { showCreateModal = false; router.reload({ preserveScroll: true }); }" />

        <CustomerAccountLedger v-if="showLedgerModal" :account="selectedAccount"
            @form-closed="showLedgerModal = false" />
        <CustomerAccountPayment v-if="showPaymentModal" :account="selectedAccount"
            @form-closed="showPaymentModal = false" />
        <CustomerAccountInvoice v-if="showInvoiceModal" :account="selectedAccount"
            @form-closed="showInvoiceModal = false" />
    </AppLayout>
</template>
