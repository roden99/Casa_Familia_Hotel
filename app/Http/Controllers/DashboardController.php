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

        // Merge both collections by account id
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

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_customers'     => $totalCustomers,
                'total_drugstores'    => $totalDrugstores,
                'total_doctors'       => $totalDoctors,
                'total_collectibles'  => number_format((float) $totalCollectibles, 2),
                'payments_this_month' => number_format((float) $paymentsThisMonth, 2),
                'sales_by_account'    => $salesByAccount,
                'month_label'         => $now->format('F Y'),
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
                'borderColor'     => $color,
                'backgroundColor' => $color . '20',
                'borderWidth'     => 2,
                'pointRadius'     => 4,
                'tension'         => 0.3,
                'fill'            => false,
            ];
        })->values();

        return response()->json([
            'labels'     => $labels,
            'datasets'   => $datasets,
            'year_label' => (string) $year,
        ]);
    }
}
