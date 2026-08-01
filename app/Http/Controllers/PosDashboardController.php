<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PosDashboardController extends Controller
{
    public function index()
    {
        $now   = Carbon::now();
        $today = $now->toDateString();
        $year  = $now->year;
        $month = $now->month;

        // ── Today ────────────────────────────────────────────────────────
        $todayRevenue = DB::table('pos_transaction_items as pti')
            ->join('pos_transactions as pt', 'pt.id', '=', 'pti.pos_transaction_id')
            ->whereDate('pt.sale_date', $today)
            ->sum('pti.total_price');

        $todayTransactions = DB::table('pos_transactions')
            ->whereDate('sale_date', $today)
            ->count();

        $todayAvg = $todayTransactions > 0 ? $todayRevenue / $todayTransactions : 0;

        // ── This month ───────────────────────────────────────────────────
        $monthRevenue = DB::table('pos_transaction_items as pti')
            ->join('pos_transactions as pt', 'pt.id', '=', 'pti.pos_transaction_id')
            ->whereYear('pt.sale_date', $year)
            ->whereMonth('pt.sale_date', $month)
            ->sum('pti.total_price');

        $monthTransactions = DB::table('pos_transactions')
            ->whereYear('sale_date', $year)
            ->whereMonth('sale_date', $month)
            ->count();

        // ── Last 30 days daily sales (for chart) ─────────────────────────
        $dailySalesRaw = DB::table('pos_transaction_items as pti')
            ->join('pos_transactions as pt', 'pt.id', '=', 'pti.pos_transaction_id')
            ->where('pt.sale_date', '>=', $now->copy()->subDays(29)->startOfDay())
            ->selectRaw('DATE(pt.sale_date) as sale_day, SUM(pti.total_price) as total')
            ->groupBy(DB::raw('DATE(pt.sale_date)'))
            ->orderBy('sale_day')
            ->pluck('total', 'sale_day');

        $dailyLabels = [];
        $dailyAmounts = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i)->toDateString();
            $dailyLabels[]  = Carbon::parse($day)->format('M d');
            $dailyAmounts[] = round((float)($dailySalesRaw[$day] ?? 0), 2);
        }

        // ── Recent transactions ───────────────────────────────────────────
        $recentTransactions = DB::table('pos_transactions as pt')
            ->leftJoin('customers as c', 'c.id', '=', 'pt.customer_id')
            ->leftJoin('pos_transaction_items as pti', 'pti.pos_transaction_id', '=', 'pt.id')
            ->selectRaw('
                pt.id,
                pt.receipt_no,
                pt.sale_date,
                pt.payment_method,
                COALESCE(c.company, CONCAT(c.last_name, ", ", c.first_name), "Walk-in") as customer_name,
                SUM(pti.total_price) as total_amount
            ')
            ->groupBy('pt.id', 'pt.receipt_no', 'pt.sale_date', 'pt.payment_method', 'c.company', 'c.last_name', 'c.first_name')
            ->orderByDesc('pt.id')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'id'             => $r->id,
                'receipt_no'     => $r->receipt_no ?? '—',
                'sale_date'      => Carbon::parse($r->sale_date)->format('m-d-Y H:i'),
                'payment_method' => strtoupper($r->payment_method ?? ''),
                'customer_name'  => $r->customer_name ?? 'Walk-in',
                'total_amount'   => number_format((float)$r->total_amount, 2),
            ]);

        // ── Top selling products this month ──────────────────────────────
        $topProducts = DB::table('pos_transaction_items as pti')
            ->join('pos_transactions as pt', 'pt.id', '=', 'pti.pos_transaction_id')
            ->join('products as p', 'p.id', '=', 'pti.product_id')
            ->whereYear('pt.sale_date', $year)
            ->whereMonth('pt.sale_date', $month)
            ->selectRaw('p.productname, SUM(pti.quantity) as total_qty, SUM(pti.total_price) as total_revenue')
            ->groupBy('p.id', 'p.productname')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'productname'   => $r->productname,
                'total_qty'     => number_format((float)$r->total_qty, 2),
                'total_revenue' => number_format((float)$r->total_revenue, 2),
            ]);

        // ── Low POS stock (pos_qty low on initialized products) ──────────
        $lowPosStock = DB::table('products')
            ->whereNotNull('initial_pos_date')
            ->where('status', true)
            ->where('is_inventory', true)
            ->where('pos_qty', '<=', 10)
            ->orderBy('pos_qty')
            ->limit(10)
            ->get(['id', 'productname', 'pos_qty']);

        return Inertia::render('POS/POSDashboard', [
            'stats' => [
                'today_revenue'      => number_format((float)$todayRevenue, 2),
                'today_transactions' => $todayTransactions,
                'today_avg'          => number_format($todayAvg, 2),
                'month_revenue'      => number_format((float)$monthRevenue, 2),
                'month_transactions' => $monthTransactions,
                'daily_labels'       => $dailyLabels,
                'daily_amounts'      => $dailyAmounts,
                'recent_transactions' => $recentTransactions,
                'top_products'       => $topProducts,
                'low_pos_stock'      => $lowPosStock,
                'month_label'        => $now->format('F Y'),
            ],
        ]);
    }
}
