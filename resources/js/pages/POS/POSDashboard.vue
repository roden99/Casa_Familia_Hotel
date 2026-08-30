<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS, CategoryScale, LinearScale, BarElement,
    Title, Tooltip, Legend,
} from 'chart.js';
import { computed } from 'vue';
import {
    ShoppingCart, DollarSign, TrendingUp, Receipt,
    PackageX, AlertTriangle,
} from 'lucide-vue-next';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'POS Overview', href: '/pos-dashboard' },
];

const props = defineProps<{
    stats: {
        today_revenue: string;
        today_transactions: number;
        today_avg: string;
        month_revenue: string;
        month_transactions: number;
        daily_labels: string[];
        daily_amounts: number[];
        recent_transactions: {
            id: number;
            receipt_no: string;
            sale_date: string;
            payment_method: string;
            customer_name: string;
            total_amount: string;
        }[];
        top_products: {
            productname: string;
            total_qty: string;
            total_revenue: string;
        }[];
        low_pos_stock: {
            id: number;
            productname: string;
            pos_qty: number;
        }[];
        month_label: string;
    };
}>();

const chartData = computed(() => ({
    labels: props.stats.daily_labels,
    datasets: [{
        label: 'Daily POS Sales',
        data: props.stats.daily_amounts,
        backgroundColor: 'rgba(16,185,129,0.75)',
        borderColor: '#10b981',
        borderWidth: 1,
        borderRadius: 4,
    }],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: { mode: 'index' as const, intersect: false },
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 10 } } },
    },
};

const summaryCards = computed(() => [
    {
        title: "Today's Revenue",
        value: `₱ ${props.stats.today_revenue}`,
        sub: `${props.stats.today_transactions} transaction${props.stats.today_transactions !== 1 ? 's' : ''}`,
        icon: DollarSign,
        color: 'text-emerald-600',
        bg: 'bg-emerald-50 dark:bg-emerald-950/30',
    },
    {
        title: 'Avg Transaction Today',
        value: `₱ ${props.stats.today_avg}`,
        sub: 'per transaction',
        icon: TrendingUp,
        color: 'text-blue-600',
        bg: 'bg-blue-50 dark:bg-blue-950/30',
    },
    {
        title: `Revenue — ${props.stats.month_label}`,
        value: `₱ ${props.stats.month_revenue}`,
        sub: `${props.stats.month_transactions} transaction${props.stats.month_transactions !== 1 ? 's' : ''}`,
        icon: Receipt,
        color: 'text-violet-600',
        bg: 'bg-violet-50 dark:bg-violet-950/30',
    },
    {
        title: 'Low POS Stock Items',
        value: String(props.stats.low_pos_stock.length),
        sub: 'qty ≤ 10',
        icon: props.stats.low_pos_stock.length > 0 ? AlertTriangle : ShoppingCart,
        color: props.stats.low_pos_stock.length > 0 ? 'text-orange-500' : 'text-gray-500',
        bg: props.stats.low_pos_stock.length > 0 ? 'bg-orange-50 dark:bg-orange-950/30' : 'bg-muted/40',
    },
]);
</script>

<template>

    <Head title="POS Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-5 p-4">

            <!-- Summary cards -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <Card v-for="card in summaryCards" :key="card.title">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ card.title }}</CardTitle>
                        <div :class="['rounded-lg p-2', card.bg]">
                            <component :is="card.icon" :class="['h-4 w-4', card.color]" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xl font-bold">{{ card.value }}</p>
                        <p class="text-xs text-muted-foreground mt-1">{{ card.sub }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Chart + Low Stock -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

                <!-- Daily sales chart -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="text-sm font-semibold">Daily POS Sales — Last 30 Days</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-64">
                            <Bar :data="chartData" :options="chartOptions" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Low POS stock -->
                <Card>
                    <CardHeader class="flex flex-row items-center gap-2">
                        <PackageX class="h-4 w-4 text-orange-500" />
                        <CardTitle class="text-sm font-semibold">Low POS Stock</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-y-auto max-h-64">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Product</TableHead>
                                        <TableHead class="text-right w-20">POS Qty</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-if="stats.low_pos_stock.length === 0">
                                        <TableCell colspan="2" class="text-center text-muted-foreground py-4 text-xs">
                                            All POS stock levels are healthy.
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-for="item in stats.low_pos_stock" :key="item.id">
                                        <TableCell class="text-xs py-2 break-words">{{ item.productname }}</TableCell>
                                        <TableCell class="text-right font-mono text-xs py-2"
                                            :class="item.pos_qty <= 0 ? 'text-destructive font-bold' : 'text-orange-500'">
                                            {{ item.pos_qty }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

            </div>

            <!-- Recent Transactions + Top Products -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

                <!-- Recent transactions -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-semibold">Recent Transactions</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-y-auto max-h-72">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Receipt</TableHead>
                                        <TableHead>Date & Time</TableHead>
                                        <TableHead>Customer</TableHead>
                                        <TableHead class="text-right">Amount</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-if="stats.recent_transactions.length === 0">
                                        <TableCell colspan="4" class="text-center text-muted-foreground py-4 text-xs">
                                            No transactions yet.
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-for="tx in stats.recent_transactions" :key="tx.id">
                                        <TableCell class="text-xs py-2 font-mono">{{ tx.receipt_no }}</TableCell>
                                        <TableCell class="text-xs py-2 whitespace-nowrap">{{ tx.sale_date }}</TableCell>
                                        <TableCell class="text-xs py-2 truncate max-w-[120px]">{{ tx.customer_name }}
                                        </TableCell>
                                        <TableCell class="text-right text-xs py-2 font-medium">₱ {{ tx.total_amount }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Top selling products this month -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-semibold">Top Products — {{ stats.month_label }}</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-y-auto max-h-72">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Product</TableHead>
                                        <TableHead class="text-right w-20">Qty Sold</TableHead>
                                        <TableHead class="text-right w-28">Revenue</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-if="stats.top_products.length === 0">
                                        <TableCell colspan="3" class="text-center text-muted-foreground py-4 text-xs">
                                            No sales this month yet.
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-for="(prod, i) in stats.top_products" :key="i">
                                        <TableCell class="text-xs py-2 break-words">{{ prod.productname }}</TableCell>
                                        <TableCell class="text-right text-xs py-2">{{ prod.total_qty }}</TableCell>
                                        <TableCell class="text-right text-xs py-2 font-medium">₱ {{ prod.total_revenue
                                            }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

            </div>

        </div>
    </AppLayout>
</template>
