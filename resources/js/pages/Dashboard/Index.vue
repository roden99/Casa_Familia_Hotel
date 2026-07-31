<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import {
  Users, Hospital, User, TrendingUp, CreditCard,
  AlertTriangle, PackageX, CalendarClock, BadgeDollarSign,
} from 'lucide-vue-next';
import { Bar, Line } from 'vue-chartjs';
import {
  Chart as ChartJS, CategoryScale, LinearScale, BarElement,
  LineElement, PointElement, Title, Tooltip, Legend, Filler,
} from 'chart.js';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Title, Tooltip, Legend, Filler);

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

// ── Monthly sales sparkline ─────────────────────────────────────────────────
const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const sparklineData = computed(() => ({
  labels: monthLabels,
  datasets: [{
    label: 'Sales',
    data: props.stats.monthly_sales,
    fill: true,
    borderColor: '#10b981',
    backgroundColor: 'rgba(16,185,129,0.12)',
    borderWidth: 2,
    pointRadius: 3,
    tension: 0.4,
  }],
}));

const sparklineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false }, tooltip: { mode: 'index' as const, intersect: false } },
  scales: {
    x: { grid: { display: false }, ticks: { font: { size: 11 } } },
    y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 11 } } },
  },
};

// ── Account bar chart (from chartData endpoint) ─────────────────────────────
const barChartData = ref<any>(null);
const selectedYear = ref(parseInt(props.stats.year_label));
const barLoading = ref(false);

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom' as const, labels: { font: { size: 11 }, boxWidth: 12 } },
    tooltip: { mode: 'index' as const, intersect: false },
  },
  scales: {
    x: { stacked: false, grid: { display: false }, ticks: { font: { size: 11 } } },
    y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 11 } } },
  },
};

async function loadBarChart() {
  barLoading.value = true;
  try {
    const res = await axios.get('/dashboard/chart-data', { params: { year: selectedYear.value } });
    barChartData.value = { labels: res.data.labels, datasets: res.data.datasets };
  } finally {
    barLoading.value = false;
  }
}

onMounted(loadBarChart);

// ── Helpers ─────────────────────────────────────────────────────────────────
const stockPercent = (item: { product_qty: number; reorder_level: number }) =>
  Math.min(100, Math.round((item.product_qty / (item.reorder_level || 1)) * 100));

const daysUntilExpiry = (date: string) =>
  Math.ceil((new Date(date).getTime() - Date.now()) / 86_400_000);
</script>

<template>

  <Head title="Dashboard" />
  <AppLayout>
    <div class="flex flex-1 flex-col gap-5 p-5">

      <!-- ── KPI Row ── -->
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">

        <Card class="border-l-4 border-l-emerald-500">
          <CardHeader class="flex flex-row items-center justify-between pb-1">
            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Sales This Month
            </CardTitle>
            <TrendingUp class="h-4 w-4 text-emerald-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">₱{{ stats.sales_this_month }}</div>
            <p class="text-xs text-muted-foreground mt-0.5">{{ stats.month_label }}</p>
          </CardContent>
        </Card>

        <Card class="border-l-4 border-l-blue-500">
          <CardHeader class="flex flex-row items-center justify-between pb-1">
            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Payments Collected
            </CardTitle>
            <CreditCard class="h-4 w-4 text-blue-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">₱{{ stats.payments_this_month }}</div>
            <p class="text-xs text-muted-foreground mt-0.5">{{ stats.month_label }}</p>
          </CardContent>
        </Card>

        <Card class="border-l-4 border-l-amber-500">
          <CardHeader class="flex flex-row items-center justify-between pb-1">
            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Total Receivables
            </CardTitle>
            <BadgeDollarSign class="h-4 w-4 text-amber-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">₱{{ stats.total_collectibles }}</div>
            <p class="text-xs text-muted-foreground mt-0.5">Outstanding AR</p>
          </CardContent>
        </Card>

        <Card class="border-l-4 border-l-violet-500">
          <CardHeader class="flex flex-row items-center justify-between pb-1">
            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Customers</CardTitle>
            <Users class="h-4 w-4 text-violet-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.total_customers }}</div>
            <p class="text-xs text-muted-foreground mt-0.5">
              <span class="text-green-600 font-medium">{{ stats.total_drugstores }}</span> drugstores ·
              <span class="text-blue-600 font-medium">{{ stats.total_doctors }}</span> doctors
            </p>
          </CardContent>
        </Card>

        <Card :class="stats.low_stock_count > 0 ? 'border-l-4 border-l-red-500' : 'border-l-4 border-l-slate-300'">
          <CardHeader class="flex flex-row items-center justify-between pb-1">
            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Low Stock</CardTitle>
            <AlertTriangle
              :class="stats.low_stock_count > 0 ? 'h-4 w-4 text-red-500' : 'h-4 w-4 text-muted-foreground'" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold" :class="stats.low_stock_count > 0 ? 'text-red-600' : ''">
              {{ stats.low_stock_count }}
            </div>
            <p class="text-xs text-muted-foreground mt-0.5">Products at/below reorder level</p>
          </CardContent>
        </Card>

      </div>

      <!-- ── Charts Row ── -->
      <div class="grid gap-4 lg:grid-cols-3">

        <!-- Monthly Sales Line Chart -->
        <Card class="lg:col-span-2">
          <CardHeader class="pb-2">
            <div class="flex items-center justify-between">
              <div>
                <CardTitle class="text-sm font-semibold">Monthly Sales Overview</CardTitle>
                <CardDescription class="text-xs">Total sales per month — {{ stats.year_label }}</CardDescription>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <div class="h-56">
              <Line :data="sparklineData" :options="sparklineOptions" />
            </div>
          </CardContent>
        </Card>

        <!-- Sales by Account this month -->
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-semibold">Sales by Account</CardTitle>
            <CardDescription class="text-xs">{{ stats.month_label }}</CardDescription>
          </CardHeader>
          <CardContent class="px-4">
            <div v-if="stats.sales_by_account.length === 0" class="text-xs text-muted-foreground py-4 text-center">
              No sales recorded this month.
            </div>
            <ul class="space-y-2">
              <li v-for="(acct, i) in stats.sales_by_account" :key="i"
                class="flex items-center justify-between text-sm">
                <span class="truncate max-w-[160px] text-xs font-medium">{{ acct.account_name }}</span>
                <span class="text-xs font-semibold text-emerald-700 tabular-nums">₱{{ acct.amount }}</span>
              </li>
            </ul>
          </CardContent>
        </Card>

      </div>

      <!-- ── Account Bar Chart Row ── -->
      <Card>
        <CardHeader class="pb-2">
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="text-sm font-semibold">Sales by Account — Monthly Breakdown</CardTitle>
              <CardDescription class="text-xs">Per-account sales across all months</CardDescription>
            </div>
            <div class="flex items-center gap-2">
              <input type="number" v-model.number="selectedYear" min="2020" :max="new Date().getFullYear() + 1"
                class="w-20 rounded border px-2 py-1 text-xs" @change="loadBarChart" />
              <button @click="loadBarChart"
                class="rounded bg-slate-100 px-2 py-1 text-xs hover:bg-slate-200">Refresh</button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="h-64">
            <Bar v-if="barChartData" :data="barChartData" :options="barOptions" />
            <div v-else class="flex h-full items-center justify-center text-xs text-muted-foreground">
              Loading chart...
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- ── Alerts Row ── -->
      <div class="grid gap-4 lg:grid-cols-2">

        <!-- Low Stock Table -->
        <Card>
          <CardHeader class="pb-2">
            <div class="flex items-center gap-2">
              <PackageX class="h-4 w-4 text-red-500" />
              <CardTitle class="text-sm font-semibold">Low Stock Alerts</CardTitle>
            </div>
            <CardDescription class="text-xs">Products at or below reorder level</CardDescription>
          </CardHeader>
          <CardContent class="px-4">
            <div v-if="stats.low_stock_items.length === 0" class="py-6 text-center text-xs text-muted-foreground">
              All products are sufficiently stocked.
            </div>
            <table v-else class="w-full text-xs">
              <thead>
                <tr class="border-b text-muted-foreground">
                  <th class="pb-1 text-left font-medium">Product</th>
                  <th class="pb-1 text-center font-medium w-16">Stock</th>
                  <th class="pb-1 text-center font-medium w-16">Reorder</th>
                  <th class="pb-1 text-right font-medium w-24">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="item in stats.low_stock_items" :key="item.id">
                  <td class="py-1.5 pr-2 truncate max-w-[160px]">{{ item.productname }}</td>
                  <td class="py-1.5 text-center font-semibold"
                    :class="item.product_qty <= 0 ? 'text-red-600' : 'text-amber-600'">
                    {{ item.product_qty }}
                  </td>
                  <td class="py-1.5 text-center text-muted-foreground">{{ item.reorder_level }}</td>
                  <td class="py-1.5 text-right">
                    <span v-if="item.product_qty <= 0"
                      class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700">
                      Out
                    </span>
                    <span v-else class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                      Low
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </CardContent>
        </Card>

        <!-- Expiring Soon Table -->
        <Card>
          <CardHeader class="pb-2">
            <div class="flex items-center gap-2">
              <CalendarClock class="h-4 w-4 text-orange-500" />
              <CardTitle class="text-sm font-semibold">Expiring Soon</CardTitle>
            </div>
            <CardDescription class="text-xs">Lots expiring within the next 90 days</CardDescription>
          </CardHeader>
          <CardContent class="px-4">
            <div v-if="stats.expiring_soon.length === 0" class="py-6 text-center text-xs text-muted-foreground">
              No lots expiring within 90 days.
            </div>
            <table v-else class="w-full text-xs">
              <thead>
                <tr class="border-b text-muted-foreground">
                  <th class="pb-1 text-left font-medium">Product</th>
                  <th class="pb-1 text-left font-medium w-24">Lot</th>
                  <th class="pb-1 text-center font-medium w-20">Expiry</th>
                  <th class="pb-1 text-center font-medium w-12">Qty</th>
                  <th class="pb-1 text-right font-medium w-16">Days</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="(lot, i) in stats.expiring_soon" :key="i">
                  <td class="py-1.5 pr-2 truncate max-w-[130px]">{{ lot.productname }}</td>
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

    </div>
  </AppLayout>
</template>
