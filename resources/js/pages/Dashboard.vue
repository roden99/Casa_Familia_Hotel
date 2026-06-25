<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, Hospital, User, Quote, CalendarDays, Wallet, BadgeDollarSign, TrendingUp } from 'lucide-vue-next';
import type { AppPageProps } from '@/types';
import { computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const page = usePage<AppPageProps>();
const user = page.props.auth.user;
const quote = page.props.quote;

const today = new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
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
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">

            <!-- Welcome Hero Banner -->
            <div class="relative overflow-hidden rounded-xl bg-primary p-6 text-primary-foreground">
                <!-- decorative circles -->
                <div class="pointer-events-none absolute -top-8 -right-8 h-40 w-40 rounded-full bg-white/10" />
                <div class="pointer-events-none absolute -bottom-10 -right-24 h-56 w-56 rounded-full bg-white/5" />

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <!-- Left: greeting + quote -->
                    <div class="flex flex-col gap-1">
                        <p class="text-xs font-medium text-primary-foreground/60 uppercase tracking-widest">Good day</p>
                        <h1 class="text-xl font-bold">{{ user.name }}</h1>
                        <div class="flex items-start gap-2 text-primary-foreground/80 mt-1 max-w-xl">
                            <Quote class="h-3.5 w-3.5 mt-0.5 shrink-0 opacity-60" />
                            <p class="text-xs italic leading-relaxed">
                                "{{ quote.message }}"
                                <span class="not-italic font-semibold text-primary-foreground ml-1">— {{ quote.author
                                    }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Right: date block -->
                    <div class="flex items-center gap-3 shrink-0 bg-white/10 rounded-xl px-4 py-3 backdrop-blur-sm">
                        <div class="flex flex-col items-center justify-center bg-white/20 rounded-lg w-14 h-14">
                            <span class="text-3xl font-bold leading-none">{{ dayNum }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-base font-semibold">{{ dayName }}</span>
                            <span class="text-sm text-primary-foreground/70">{{ monthYear }}</span>
                            <div class="flex items-center gap-1 mt-0.5">
                                <CalendarDays class="h-3 w-3 opacity-60" />
                                <span class="text-xs text-primary-foreground/60">Today</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Stat Cards -->
            <div class="grid gap-4 md:grid-cols-3">

                <Card class="border-l-4 border-l-primary">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Customers</CardTitle>
                        <div class="rounded-lg bg-primary/10 p-2">
                            <Users class="h-4 w-4 text-primary" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-primary">{{ stats.total_customers }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Active customers</p>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-green-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Drugstores</CardTitle>
                        <div class="rounded-lg bg-green-500/10 p-2">
                            <Hospital class="h-4 w-4 text-green-600" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-green-600">{{ stats.total_drugstores }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Active drugstore accounts</p>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-blue-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Doctors</CardTitle>
                        <div class="rounded-lg bg-blue-500/10 p-2">
                            <User class="h-4 w-4 text-blue-600" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-blue-600">{{ stats.total_doctors }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Active doctor accounts</p>
                    </CardContent>
                </Card>

            </div>

            <!-- AR / Financial Stat Cards -->
            <div class="grid gap-4 md:grid-cols-2">

                <Card class="border-l-4 border-l-destructive">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Collectibles</CardTitle>
                        <div class="rounded-lg bg-destructive/10 p-2">
                            <Wallet class="h-4 w-4 text-destructive" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold font-mono text-destructive">{{ stats.total_collectibles }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Total outstanding AR balance</p>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-emerald-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Payments — {{ stats.month_label }}</CardTitle>
                        <div class="rounded-lg bg-emerald-500/10 p-2">
                            <BadgeDollarSign class="h-4 w-4 text-emerald-600" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold font-mono text-emerald-600">{{ stats.payments_this_month }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Total payments collected this month</p>
                    </CardContent>
                </Card>

            </div>

            <!-- Sales by Account this month -->
            <Card class="border-t-4 border-t-primary">
                <CardHeader class="pb-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <CardTitle class="text-base font-semibold flex items-center gap-2">
                                <TrendingUp class="h-5 w-5 text-primary" />
                                Sales by Account
                            </CardTitle>
                            <p class="text-xs text-muted-foreground mt-0.5">{{ stats.month_label }}</p>
                        </div>
                        <div v-if="stats.sales_by_account.length > 0" class="text-right">
                            <p class="text-xs text-muted-foreground">Total</p>
                            <p class="font-mono text-base font-bold text-primary">
                                {{stats.sales_by_account.reduce((s, r) => s + r.raw, 0).toLocaleString('en-US', {
                                    minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                            </p>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="stats.sales_by_account.length === 0"
                        class="text-sm text-muted-foreground py-6 text-center">
                        No sales recorded this month.
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="(row, i) in stats.sales_by_account" :key="row.account_name"
                            class="group relative rounded-lg border bg-muted/20 hover:bg-muted/50 transition-colors overflow-hidden">
                            <!-- progress fill -->
                            <div class="absolute inset-y-0 left-0 bg-primary/8 transition-all"
                                :style="{ width: (stats.sales_by_account[0].raw > 0 ? (row.raw / stats.sales_by_account[0].raw) * 100 : 0) + '%' }" />
                            <!-- content -->
                            <div class="relative flex items-center gap-3 px-4 py-3">
                                <div :class="[
                                    'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                    i === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400' :
                                        i === 1 ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' :
                                            i === 2 ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400' :
                                                'bg-muted text-muted-foreground'
                                ]">
                                    {{ i + 1 }}
                                </div>
                                <span class="flex-1 text-sm font-semibold tracking-wide">{{ row.account_name }}</span>
                                <span class="font-mono text-sm font-bold text-primary">{{ row.amount }}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
