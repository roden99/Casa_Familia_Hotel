<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, Hospital, User, Quote, CalendarDays, Wallet, BadgeDollarSign, TrendingUp, Loader2 } from 'lucide-vue-next';
import type { AppPageProps } from '@/types';
import { computed, ref, onMounted, watch } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import axios from 'axios';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

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

defineProps<{
    stats: {
        total_customers: number;
        total_drugstores: number;
        total_doctors: number;
        total_collectibles: string;
        payments_this_month: string;
        sales_by_account: { account_name: string; amount: string; raw: number }[];
        month_label: string;
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
            },
        },
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        y: {
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

            <!-- Sales Line Chart -->
            <Card class="border-t-4 border-t-primary flex flex-col flex-1 min-h-0">
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
                <CardContent class="flex-1 min-h-0 pb-4">
                    <!-- Loading -->
                    <div v-if="isChartLoading"
                        class="flex h-full items-center justify-center text-muted-foreground gap-2 text-sm">
                        <Loader2 class="h-4 w-4 animate-spin" /> Loading chart...
                    </div>
                    <!-- Empty -->
                    <div v-else-if="chartData.datasets.length === 0 || chartData.datasets.every(d => d.data.every((v: number) => v === 0))"
                        class="flex h-full items-center justify-center text-muted-foreground text-sm">
                        No sales data for {{ chartLabel }}.
                    </div>
                    <!-- Chart -->
                    <div v-else class="h-full">
                        <Line :data="chartData" :options="chartOptions" />
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
