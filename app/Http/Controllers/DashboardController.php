<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now   = Carbon::now();
        $year  = $now->year;
        $month = $now->month;

        // ── Customer counts ─────────────────────────────────────────────
        $totalCustomers  = Customer::where('status', 'active')->count();
        $totalDrugstores = Customer::where('status', 'active')->where('is_drugstore', true)->count();
        $totalDoctors    = Customer::where('status', 'active')->where('is_drugstore', false)->count();

        // ── Total collectibles (overall outstanding AR) ──────────────────
        $totalCollectibles = DB::table('customer_sales_account as csa')
            ->selectRaw('
                SUM(IFNULL(csa.forward_balance, 0))
                + IFNULL((
                    SELECT SUM(soi2.quantity * soi2.unit_price * (1 - IFNULL(soi2.discount_percentage,0)/100))
                    FROM sales_orders so2
                    JOIN sales_order_items soi2 ON soi2.sales_order_id = so2.id
                ), 0)
                + IFNULL((
                    SELECT SUM(i2.amount) FROM customer_account_invoices i2
                ), 0)
                - IFNULL((
                    SELECT SUM(p2.amount) FROM customer_sales_account_payments p2
                ), 0) as total
            ')
            ->value('total') ?? 0;

        // ── Payments made this month ─────────────────────────────────────
        $paymentsThisMonth = DB::table('customer_sales_account_payments')
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->sum('amount');

        // ── Sales by account this month (SO + manual invoices) ───────────
        $soByAccount = DB::table('sales_orders as so')
            ->join('sales_order_items as soi', 'soi.sales_order_id', '=', 'so.id')
            ->join('customer_sales_account as csa', 'csa.id', '=', 'so.customer_sales_account_id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->whereYear('so.invoice_date', $year)
            ->whereMonth('so.invoice_date', $month)
            ->selectRaw('sa.id as sa_id, sa.account_name, SUM(soi.quantity * soi.unit_price * (1 - IFNULL(soi.discount_percentage,0)/100)) as amount')
            ->groupBy('sa.id', 'sa.account_name')
            ->get()
            ->keyBy('sa_id');

        $invByAccount = DB::table('customer_account_invoices as i')
            ->join('customer_sales_account as csa', 'csa.id', '=', 'i.customer_sales_account_id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->whereYear('i.invoice_date', $year)
            ->whereMonth('i.invoice_date', $month)
            ->selectRaw('sa.id as sa_id, sa.account_name, SUM(i.amount) as amount')
            ->groupBy('sa.id', 'sa.account_name')
            ->get()
            ->keyBy('sa_id');

        $allIds = collect($soByAccount->keys())->merge($invByAccount->keys())->unique();

        $salesByAccount = $allIds->map(function ($saId) use ($soByAccount, $invByAccount) {
            $soRow  = $soByAccount->get($saId);
            $invRow = $invByAccount->get($saId);
            $name   = $soRow?->account_name ?? $invRow?->account_name;
            $total  = ((float) ($soRow?->amount ?? 0)) + ((float) ($invRow?->amount ?? 0));
            return [
                'account_name' => strtoupper($name),
                'amount'       => number_format($total, 2),
                'raw'          => $total,
            ];
        })->sortByDesc('raw')->values();

        $salesThisMonth = $salesByAccount->sum('raw');

        // ── Low stock items ──────────────────────────────────────────────
        $lowStockItems = DB::table('products as p')
            ->leftJoin('drugforms as df', 'df.id', '=', 'p.drugform_id')
            ->leftJoin('product_units as pu', 'pu.id', '=', 'p.product_unit_id')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->whereColumn('p.product_qty', '<=', 'p.reorder_level')
            ->where('p.status', true)
            ->where('p.is_inventory', true)
            ->whereNotNull('p.reorder_level')
            ->where('p.reorder_level', '>', 0)
            ->orderByRaw('p.product_qty - p.reorder_level ASC')
            ->limit(10)
            ->selectRaw("p.id, TRIM(CONCAT_WS(' ', p.productname, df.drugformname, pu.unit_name, IF(b.brandname IS NOT NULL, CONCAT('(', b.brandname, ')'), NULL))) as productname, p.product_qty, p.reorder_level")
            ->get();

        $lowStockCount = DB::table('products')
            ->whereColumn('product_qty', '<=', 'reorder_level')
            ->where('status', true)
            ->where('is_inventory', true)
            ->whereNotNull('reorder_level')
            ->where('reorder_level', '>', 0)
            ->count();

        // ── Expiring soon (within 90 days) ───────────────────────────────
        $expiringSoon = DB::table('product_lots as pl')
            ->join('products as p', 'p.id', '=', 'pl.product_id')
            ->leftJoin('drugforms as df', 'df.id', '=', 'p.drugform_id')
            ->leftJoin('product_units as pu', 'pu.id', '=', 'p.product_unit_id')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->where('pl.expiration_date', '<=', $now->copy()->addDays(90)->toDateString())
            ->where('pl.expiration_date', '>=', $now->toDateString())
            ->where('pl.quantity', '>', 0)
            ->orderBy('pl.expiration_date')
            ->limit(10)
            ->selectRaw("TRIM(CONCAT_WS(' ', p.productname, df.drugformname, pu.unit_name, IF(b.brandname IS NOT NULL, CONCAT('(', b.brandname, ')'), NULL))) as productname, pl.lot_number, pl.expiration_date, pl.quantity")
            ->get();

        // ── Monthly sales total this year (for sparkline) ────────────────
        $soMonthly = DB::table('sales_orders as so')
            ->join('sales_order_items as soi', 'soi.sales_order_id', '=', 'so.id')
            ->whereYear('so.invoice_date', $year)
            ->whereNotNull('so.invoice_date')
            ->selectRaw('MONTH(so.invoice_date) as month, SUM(soi.quantity * soi.unit_price * (1 - IFNULL(soi.discount_percentage,0)/100)) as amount')
            ->groupBy(DB::raw('MONTH(so.invoice_date)'))
            ->pluck('amount', 'month');

        $invMonthly = DB::table('customer_account_invoices as i')
            ->whereYear('i.invoice_date', $year)
            ->whereNotNull('i.invoice_date')
            ->selectRaw('MONTH(i.invoice_date) as month, SUM(i.amount) as amount')
            ->groupBy(DB::raw('MONTH(i.invoice_date)'))
            ->pluck('amount', 'month');

        $monthlySales = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlySales[] = round(((float)($soMonthly[$m] ?? 0)) + ((float)($invMonthly[$m] ?? 0)), 2);
        }

        $cutoff = $now->copy()->subDays(90)->toDateString();

        // ── Fast moving items (top 10 by qty sold in last 90 days) ──────
        $fastMoving = DB::table('sales_order_items as soi')
            ->join('sales_orders as so', 'so.id', '=', 'soi.sales_order_id')
            ->join('products as p', 'p.id', '=', 'soi.product_id')
            ->leftJoin('drugforms as df', 'df.id', '=', 'p.drugform_id')
            ->leftJoin('product_units as pu', 'pu.id', '=', 'p.product_unit_id')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->where('so.invoice_date', '>=', $cutoff)
            ->selectRaw("p.id, TRIM(CONCAT_WS(' ', p.productname, df.drugformname, pu.unit_name, IF(b.brandname IS NOT NULL, CONCAT('(', b.brandname, ')'), NULL))) as productname, SUM(soi.quantity) as total_qty")
            ->groupBy('p.id', 'p.productname', 'df.drugformname', 'pu.unit_name', 'b.brandname')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        // ── Slow/not moving (has stock but 0 sales in last 90 days) ──────
        $soldIds = DB::table('sales_order_items as soi')
            ->join('sales_orders as so', 'so.id', '=', 'soi.sales_order_id')
            ->where('so.invoice_date', '>=', $cutoff)
            ->pluck('soi.product_id')
            ->unique();

        $slowMoving = DB::table('products as p')
            ->leftJoin('drugforms as df', 'df.id', '=', 'p.drugform_id')
            ->leftJoin('product_units as pu', 'pu.id', '=', 'p.product_unit_id')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->whereNotIn('p.id', $soldIds)
            ->where('p.product_qty', '>', 0)
            ->where('p.status', true)
            ->where('p.is_inventory', true)
            ->orderByDesc('p.product_qty')
            ->selectRaw("p.id, TRIM(CONCAT_WS(' ', p.productname, df.drugformname, pu.unit_name, IF(b.brandname IS NOT NULL, CONCAT('(', b.brandname, ')'), NULL))) as productname, p.product_qty")
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_customers'     => $totalCustomers,
                'total_drugstores'    => $totalDrugstores,
                'total_doctors'       => $totalDoctors,
                'total_collectibles'  => number_format((float) $totalCollectibles, 2),
                'payments_this_month' => number_format((float) $paymentsThisMonth, 2),
                'sales_this_month'    => number_format($salesThisMonth, 2),
                'sales_by_account'    => $salesByAccount,
                'low_stock_items'     => $lowStockItems,
                'low_stock_count'     => $lowStockCount,
                'expiring_soon'       => $expiringSoon,
                'monthly_sales'       => $monthlySales,
                'fast_moving_items'   => $fastMoving,
                'slow_moving_items'   => $slowMoving,
                'month_label'         => $now->format('F Y'),
                'year_label'          => (string) $year,
            ],
        ]);
    }

    public function chartData(\Illuminate\Http\Request $request)
    {
        $year = (int) $request->input('year', Carbon::now()->year);

        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        // ── Monthly SO sales per account (same join chain as index()) ─────
        $soRows = DB::table('sales_orders as so')
            ->join('sales_order_items as soi', 'soi.sales_order_id', '=', 'so.id')
            ->join('customer_sales_account as csa', 'csa.id', '=', 'so.customer_sales_account_id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->whereYear('so.invoice_date', $year)
            ->whereNotNull('so.invoice_date')
            ->selectRaw('sa.id as sa_id, sa.account_name, MONTH(so.invoice_date) as month,
                SUM(soi.quantity * soi.unit_price * (1 - IFNULL(soi.discount_percentage,0)/100)) as amount')
            ->groupBy('sa.id', 'sa.account_name', DB::raw('MONTH(so.invoice_date)'))
            ->get();

        // ── Monthly manual invoice sales per account ──────────────────────
        $invRows = DB::table('customer_account_invoices as i')
            ->join('customer_sales_account as csa', 'csa.id', '=', 'i.customer_sales_account_id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->whereYear('i.invoice_date', $year)
            ->whereNotNull('i.invoice_date')
            ->selectRaw('sa.id as sa_id, sa.account_name, MONTH(i.invoice_date) as month, SUM(i.amount) as amount')
            ->groupBy('sa.id', 'sa.account_name', DB::raw('MONTH(i.invoice_date)'))
            ->get();

        // ── Build lookup [sa_id][month] and collect unique accounts ───────
        $monthly  = [];
        $accounts = [];

        foreach ($soRows->merge($invRows) as $row) {
            $accounts[$row->sa_id] = strtoupper($row->account_name);
            $monthly[$row->sa_id][$row->month] = ($monthly[$row->sa_id][$row->month] ?? 0) + (float) $row->amount;
        }

        ksort($accounts); // sort by account id for consistent colour order

        $colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16'];

        $datasets = collect($accounts)->values()->map(function ($name, $idx) use ($accounts, $monthly, $colors) {
            $saId  = array_keys($accounts)[$idx];
            $data  = [];
            for ($m = 1; $m <= 12; $m++) {
                $data[] = round($monthly[$saId][$m] ?? 0, 2);
            }
            $color = $colors[$idx % count($colors)];
            return [
                'label'           => $name,
                'data'            => $data,
                'backgroundColor' => $color . 'cc',
                'borderColor'     => $color,
                'borderWidth'     => 1,
                'borderRadius'    => 4,
            ];
        })->values();

        return response()->json([
            'labels'     => $labels,
            'datasets'   => $datasets,
            'year_label' => (string) $year,
        ]);
    }
}
