<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, Hospital, User, Quote, CalendarDays, Wallet, BadgeDollarSign, TrendingUp, Loader2, AlertTriangle, PackageX, CalendarClock, CreditCard } from 'lucide-vue-next';
import type { AppPageProps } from '@/types';
import { computed, ref, onMounted, watch } from 'vue';
import { Bar, Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    Filler,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import axios from 'axios';

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Filler, Title, Tooltip, Legend);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];

const page = usePage<AppPageProps>();
const user = page.props.auth.user;
const quote = page.props.quote;

const today = new Date().toLocaleDateString('en-US', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
});
const dayNum = new Date().getDate();
const dayName = new Date().toLocaleDateString('en-US', { weekday: 'long' });
const monthYear = new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

const props = defineProps<{
    stats: {
        total_customers: number;
        total_drugstores: number;
        total_doctors: number;
        total_collectibles: string;
        payments_this_month: string;
        sales_this_month: string;
        sales_by_account: { account_name: string; amount: string; raw: number }[];
        low_stock_items: { id: number; productname: string; product_qty: number; reorder_level: number }[];
        low_stock_count: number;
        expiring_soon: { productname: string; lot_number: string; expiration_date: string; quantity: number }[];
        monthly_sales: number[];
        month_label: string;
        year_label: string;
    };
}>();

// ── Year selector ────────────────────────────────────────────────────────────
const currentYear = new Date().getFullYear();
const selectedYear = ref(currentYear);
const years = Array.from({ length: 5 }, (_, i) => currentYear - 3 + i);

// ── Chart data ───────────────────────────────────────────────────────────────
const isChartLoading = ref(false);
const chartLabel = ref('');
const chartData = ref<{ labels: string[]; datasets: any[] }>({ labels: [], datasets: [] });

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index' as const, intersect: false },
    plugins: {
        legend: { position: 'bottom' as const, labels: { boxWidth: 12, font: { size: 11 } } },
        tooltip: {
            callbacks: {
                label: (ctx: any) => ` ${ctx.dataset.label}: ${Number(ctx.parsed.y).toLocaleString('en-US', { minimumFractionDigits: 2 })}`,
                footer: (items: any[]) => {
                    const total = items.reduce((s, i) => s + i.parsed.y, 0);
                    return `Total: ${total.toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
                },
            },
        },
    },
    scales: {
        x: {
            stacked: false,
            grid: { display: false },
            ticks: { font: { size: 11 } },
        },
        y: {
            stacked: false,
            beginAtZero: true,
            ticks: {
                font: { size: 11 },
                callback: (v: any) => Number(v).toLocaleString('en-US', { minimumFractionDigits: 0 }),
            },
        },
    },
}));

const fetchChartData = async () => {
    isChartLoading.value = true;
    try {
        const res = await axios.get('/dashboard/chart-data', {
            params: { year: selectedYear.value },
        });
        chartData.value = { labels: res.data.labels, datasets: res.data.datasets };
        chartLabel.value = res.data.year_label;
    } catch {
        // silent
    } finally {
        isChartLoading.value = false;
    }
};

onMounted(fetchChartData);
watch([selectedYear], fetchChartData);

// ── Sparkline ────────────────────────────────────────────────────────────────
const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const sparkOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { mode: 'index' as const, intersect: false } },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        y: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } },
    },
};

const daysUntilExpiry = (date: string) =>
    Math.ceil((new Date(date).getTime() - Date.now()) / 86_400_000);

const salesByAccountData = computed(() => ({
    labels: props.stats.sales_by_account.map(a => a.account_name),
    datasets: [{
        label: 'Sales',
        data: props.stats.sales_by_account.map(a => a.raw),
        backgroundColor: 'rgba(16,185,129,0.75)',
        borderRadius: 4,
        borderSkipped: false,
    }],
}));

const salesByAccountOptions = computed(() => ({
    indexAxis: 'y' as const,
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx: any) => ` ₱${Number(ctx.parsed.x).toLocaleString('en-US', { minimumFractionDigits: 2 })}`,
            },
        },
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 }, callback: (v: any) => `₱${Number(v).toLocaleString()}` } },
        y: { grid: { display: false }, ticks: { font: { size: 10 } } },
    },
}));

const sparklineData = computed(() => ({
    labels: monthLabels,
    datasets: [{
        label: 'Sales',
        data: props.stats.monthly_sales,
        fill: true,
        borderColor: '#10b981',
        backgroundColor: 'rgba(16,185,129,0.10)',
        borderWidth: 2,
        pointRadius: 2,
        tension: 0.4,
    }],
}));
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-3 rounded-xl p-4 overflow-hidden">

            <!-- Welcome Hero Banner -->
            <div class="relative overflow-hidden rounded-lg bg-primary px-5 py-3 text-primary-foreground">
                <div class="pointer-events-none absolute -top-6 -right-6 h-24 w-24 rounded-full bg-white/10" />
                <div class="relative flex items-center justify-between gap-4">
                    <!-- Left: greeting + quote -->
                    <div class="flex flex-col gap-0.5 min-w-0">
                        <p class="text-[10px] font-medium text-primary-foreground/60 uppercase tracking-widest">Good day
                        </p>
                        <h1 class="text-base font-bold leading-tight">{{ user.name }}</h1>
                        <div class="flex items-start gap-1.5 text-primary-foreground/70 mt-0.5">
                            <Quote class="h-3 w-3 mt-0.5 shrink-0 opacity-60" />
                            <p class="text-[11px] italic leading-relaxed truncate max-w-lg">
                                "{{ quote.message }}"
                                <span class="not-italic font-semibold text-primary-foreground ml-1">— {{ quote.author
                                }}</span>
                            </p>
                        </div>
                    </div>
                    <!-- Right: date -->
                    <div class="flex items-center gap-2 shrink-0 bg-white/10 rounded-lg px-3 py-2 backdrop-blur-sm">
                        <div class="flex items-center justify-center bg-white/20 rounded-md w-8 h-8">
                            <span class="text-lg font-bold leading-none">{{ dayNum }}</span>
                        </div>
                        <div class="flex flex-col leading-tight">
                            <span class="text-sm font-semibold">{{ dayName }}</span>
                            <span class="text-xs text-primary-foreground/70">{{ monthYear }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Stat Cards -->
            <div class="grid gap-3 md:grid-cols-3">

                <Card class="border-l-4 border-l-primary py-0">
                    <CardContent class="flex items-center justify-between px-4 py-3">
                        <div>
                            <p class="text-xs text-muted-foreground font-medium">Total Customers</p>
                            <p class="text-2xl font-bold text-primary">{{ stats.total_customers }}</p>
                        </div>
                        <div class="rounded-lg bg-primary/10 p-2">
                            <Users class="h-4 w-4 text-primary" />
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-green-500 py-0">
                    <CardContent class="flex items-center justify-between px-4 py-3">
                        <div>
                            <p class="text-xs text-muted-foreground font-medium">Drugstores</p>
                            <p class="text-2xl font-bold text-green-600">{{ stats.total_drugstores }}</p>
                        </div>
                        <div class="rounded-lg bg-green-500/10 p-2">
                            <Hospital class="h-4 w-4 text-green-600" />
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-blue-500 py-0">
                    <CardContent class="flex items-center justify-between px-4 py-3">
                        <div>
                            <p class="text-xs text-muted-foreground font-medium">Doctors</p>
                            <p class="text-2xl font-bold text-blue-600">{{ stats.total_doctors }}</p>
                        </div>
                        <div class="rounded-lg bg-blue-500/10 p-2">
                            <User class="h-4 w-4 text-blue-600" />
                        </div>
                    </CardContent>
                </Card>

            </div>

            <!-- Financial KPI Cards -->
            <div class="grid gap-3 sm:grid-cols-3">

                <Card class="border-l-4 border-l-emerald-500 py-0">
                    <CardContent class="flex items-center justify-between px-4 py-3">
                        <div>
                            <p class="text-xs text-muted-foreground font-medium">Sales This Month</p>
                            <p class="text-2xl font-bold text-emerald-600">₱{{ stats.sales_this_month }}</p>
                            <p class="text-[10px] text-muted-foreground">{{ stats.month_label }}</p>
                        </div>
                        <div class="rounded-lg bg-emerald-500/10 p-2">
                            <TrendingUp class="h-4 w-4 text-emerald-600" />
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-sky-500 py-0">
                    <CardContent class="flex items-center justify-between px-4 py-3">
                        <div>
                            <p class="text-xs text-muted-foreground font-medium">Payments Collected</p>
                            <p class="text-2xl font-bold text-sky-600">₱{{ stats.payments_this_month }}</p>
                            <p class="text-[10px] text-muted-foreground">{{ stats.month_label }}</p>
                        </div>
                        <div class="rounded-lg bg-sky-500/10 p-2">
                            <CreditCard class="h-4 w-4 text-sky-600" />
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-amber-500 py-0">
                    <CardContent class="flex items-center justify-between px-4 py-3">
                        <div>
                            <p class="text-xs text-muted-foreground font-medium">Total Receivables</p>
                            <p class="text-2xl font-bold text-amber-600">₱{{ stats.total_collectibles }}</p>
                            <p class="text-[10px] text-muted-foreground">Outstanding AR</p>
                        </div>
                        <div class="rounded-lg bg-amber-500/10 p-2">
                            <BadgeDollarSign class="h-4 w-4 text-amber-600" />
                        </div>
                    </CardContent>
                </Card>

            </div>

            <!-- Monthly Sparkline + Sales by Account -->
            <div class="grid gap-3 lg:grid-cols-3">

                <Card class="lg:col-span-2">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-semibold">Monthly Sales — {{ stats.year_label }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-44">
                            <Line :data="sparklineData" :options="sparkOptions" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-semibold">Sales by Account</CardTitle>
                        <p class="text-xs text-muted-foreground">{{ stats.month_label }}</p>
                    </CardHeader>
                    <CardContent class="px-4">
                        <p v-if="stats.sales_by_account.length === 0"
                            class="text-xs text-muted-foreground py-3 text-center">
                            No sales this month.
                        </p>
                        <div v-else :style="{ height: Math.max(120, stats.sales_by_account.length * 32) + 'px' }">
                            <Bar :data="salesByAccountData" :options="salesByAccountOptions" />
                        </div>
                    </CardContent>
                </Card>

            </div>

            <!-- Low Stock + Expiring Soon -->
            <div class="grid gap-3 lg:grid-cols-2">

                <Card>
                    <CardHeader class="pb-2">
                        <div class="flex items-center gap-2">
                            <PackageX class="h-4 w-4 text-red-500" />
                            <CardTitle class="text-sm font-semibold">Low Stock
                                <span v-if="stats.low_stock_count > 0"
                                    class="ml-1 rounded-full bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700">
                                    {{ stats.low_stock_count }}
                                </span>
                            </CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent class="px-4">
                        <p v-if="stats.low_stock_items.length === 0"
                            class="text-xs text-muted-foreground py-3 text-center">
                            All products are sufficiently stocked.
                        </p>
                        <table v-else class="w-full text-xs">
                            <thead>
                                <tr class="border-b text-muted-foreground">
                                    <th class="pb-1 text-left font-medium">Product</th>
                                    <th class="pb-1 text-center font-medium w-14">Stock</th>
                                    <th class="pb-1 text-center font-medium w-14">Min</th>
                                    <th class="pb-1 text-right font-medium w-16">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="item in stats.low_stock_items" :key="item.id">
                                    <td class="py-1.5 truncate max-w-[160px]">{{ item.productname }}</td>
                                    <td class="py-1.5 text-center font-semibold"
                                        :class="item.product_qty <= 0 ? 'text-red-600' : 'text-amber-600'">
                                        {{ item.product_qty }}
                                    </td>
                                    <td class="py-1.5 text-center text-muted-foreground">{{ item.reorder_level }}</td>
                                    <td class="py-1.5 text-right">
                                        <span v-if="item.product_qty <= 0"
                                            class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700">Out</span>
                                        <span v-else
                                            class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Low</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <div class="flex items-center gap-2">
                            <CalendarClock class="h-4 w-4 text-orange-500" />
                            <CardTitle class="text-sm font-semibold">Expiring Soon <span
                                    class="text-xs font-normal text-muted-foreground">(90 days)</span></CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent class="px-4">
                        <p v-if="stats.expiring_soon.length === 0"
                            class="text-xs text-muted-foreground py-3 text-center">
                            No lots expiring within 90 days.
                        </p>
                        <table v-else class="w-full text-xs">
                            <thead>
                                <tr class="border-b text-muted-foreground">
                                    <th class="pb-1 text-left font-medium">Product</th>
                                    <th class="pb-1 text-left font-medium w-20">Lot</th>
                                    <th class="pb-1 text-center font-medium w-20">Expiry</th>
                                    <th class="pb-1 text-center font-medium w-10">Qty</th>
                                    <th class="pb-1 text-right font-medium w-14">Days</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="(lot, i) in stats.expiring_soon" :key="i">
                                    <td class="py-1.5 truncate max-w-[120px]">{{ lot.productname }}</td>
                                    <td class="py-1.5 text-muted-foreground">{{ lot.lot_number }}</td>
                                    <td class="py-1.5 text-center">{{ lot.expiration_date }}</td>
                                    <td class="py-1.5 text-center font-semibold">{{ lot.quantity }}</td>
                                    <td class="py-1.5 text-right">
                                        <span
                                            :class="daysUntilExpiry(lot.expiration_date) <= 30
                                                ? 'rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700'
                                                : 'rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-semibold text-orange-700'">
                                            {{ daysUntilExpiry(lot.expiration_date) }}d
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

            </div>

            <!-- Sales Line Chart -->
            <Card class="border-t-4 border-t-primary">
                <CardHeader class="pb-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <CardTitle class="text-base font-semibold flex items-center gap-2">
                                <TrendingUp class="h-5 w-5 text-primary" />
                                Sales by Account
                            </CardTitle>
                            <p class="text-xs text-muted-foreground mt-0.5">Monthly sales — {{ chartLabel }}</p>
                        </div>
                        <!-- Year selector -->
                        <div class="flex items-center gap-2">
                            <select v-model.number="selectedYear"
                                class="h-8 rounded-md border bg-background px-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="pb-4">
                    <!-- Loading -->
                    <div v-if="isChartLoading"
                        class="flex h-64 items-center justify-center text-muted-foreground gap-2 text-sm">
                        <Loader2 class="h-4 w-4 animate-spin" /> Loading chart...
                    </div>
                    <!-- Empty -->
                    <div v-else-if="chartData.datasets.length === 0 || chartData.datasets.every(d => d.data.every((v: number) => v === 0))"
                        class="flex h-64 items-center justify-center text-muted-foreground text-sm">
                        No sales data for {{ chartLabel }}.
                    </div>
                    <!-- Chart -->
                    <div v-else class="h-72">
                        <Bar :data="chartData" :options="chartOptions" />
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
