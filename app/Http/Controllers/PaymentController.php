<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $search        = $request->input('search');
        $column        = $request->input('column');
        $paymentMethod = $request->input('payment_method');
        $dateFrom      = $request->input('date_from');
        $dateTo        = $request->input('date_to');

        // ── CSA payments (linked to sales orders / account balance) ───────
        $csaQuery = DB::table('customer_sales_account_payments as p')
            ->join('customer_sales_account as csa', 'csa.id', '=', 'p.customer_sales_account_id')
            ->join('customers as c', 'c.id', '=', 'csa.customer_id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.created_by')
            ->select(
                'p.id',
                DB::raw("'CSA' as payment_type"),
                DB::raw("CASE WHEN c.is_drugstore = 1 THEN UPPER(c.company)
                              ELSE TRIM(CONCAT(UPPER(c.last_name), ', ', UPPER(c.first_name)))
                         END as customer_name"),
                'sa.account_name',
                'p.amount',
                'p.payment_date',
                'p.reference_no',
                'p.payment_method',
                'p.check_date',
                'p.check_number',
                'p.notes',
                'p.tag_status',
                'u.name as created_by_name',
                'p.created_at'
            );

        // ── Invoice payments (linked to customer account invoices) ─────────
        $invQuery = DB::table('customer_account_invoice_payments as p')
            ->join('customer_account_invoices as i', 'i.id', '=', 'p.customer_account_invoice_id')
            ->join('customer_sales_account as csa', 'csa.id', '=', 'i.customer_sales_account_id')
            ->join('customers as c', 'c.id', '=', 'csa.customer_id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.created_by')
            ->select(
                'p.id',
                DB::raw("'INV' as payment_type"),
                DB::raw("CASE WHEN c.is_drugstore = 1 THEN UPPER(c.company)
                              ELSE TRIM(CONCAT(UPPER(c.last_name), ', ', UPPER(c.first_name)))
                         END as customer_name"),
                'sa.account_name',
                'p.amount',
                'p.payment_date',
                'p.reference_no',
                'p.payment_method',
                'p.check_date',
                'p.check_number',
                'p.notes',
                'p.tag_status',
                'u.name as created_by_name',
                'p.created_at'
            );

        // ── Apply filters ─────────────────────────────────────────────────
        if (!empty($paymentMethod)) {
            $csaQuery->where('p.payment_method', $paymentMethod);
            $invQuery->where('p.payment_method', $paymentMethod);
        }

        if (!empty($dateFrom)) {
            $csaQuery->whereDate('p.payment_date', '>=', $dateFrom);
            $invQuery->whereDate('p.payment_date', '>=', $dateFrom);
        }

        if (!empty($dateTo)) {
            $csaQuery->whereDate('p.payment_date', '<=', $dateTo);
            $invQuery->whereDate('p.payment_date', '<=', $dateTo);
        }

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            if ($column === 'customer_name') {
                $csaQuery->where(function ($q) use ($search) {
                    $q->where('c.last_name', 'like', "{$search}%")
                        ->orWhere('c.company', 'like', "{$search}%");
                });
                $invQuery->where(function ($q) use ($search) {
                    $q->where('c.last_name', 'like', "{$search}%")
                        ->orWhere('c.company', 'like', "{$search}%");
                });
            } elseif ($column === 'reference_no') {
                $csaQuery->where('p.reference_no', 'like', "{$search}%");
                $invQuery->where('p.reference_no', 'like', "{$search}%");
            } elseif ($column === 'account_name') {
                $csaQuery->where('sa.account_name', 'like', "{$search}%");
                $invQuery->where('sa.account_name', 'like', "{$search}%");
            }
        }

        $union = $csaQuery->union($invQuery);

        $payments = DB::table(DB::raw("({$union->toSql()}) as all_payments"))
            ->mergeBindings($union)
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn($row) => [
                'id'              => $row->id,
                'payment_type'    => $row->payment_type,
                'customer_name'   => $row->customer_name,
                'account_name'    => strtoupper($row->account_name),
                'amount'          => number_format((float) $row->amount, 2, '.', ','),
                'payment_date'    => $row->payment_date ? Carbon::parse($row->payment_date)->format('m-d-Y') : null,
                'reference_no'    => $row->reference_no ?? '—',
                'payment_method'  => $row->payment_method ?? 'Cash',
                'check_date'      => $row->check_date ? Carbon::parse($row->check_date)->format('m-d-Y') : null,
                'check_number'    => $row->check_number ?? null,
                'notes'           => $row->notes ?? '',
                'tag_status'      => $row->tag_status ?? 'Untagged',
                'created_by_name' => $row->created_by_name ?? '—',
            ]);

        $columns = [
            ['accessorKey' => 'amount',         'header' => 'AMOUNT',         'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'payment_date',   'header' => 'PAYMENT DATE',   'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'payment_method', 'header' => 'METHOD',         'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'reference_no',   'header' => 'REFERENCE',      'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'check_number',   'header' => 'CHECK NO.',      'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'check_date',     'header' => 'CHECK DATE',     'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'notes',          'header' => 'NOTES',          'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'tag_status',      'header' => 'TAG STATUS',     'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'created_by_name', 'header' => 'RECORDED BY',    'isVisible' => true,  'isParameter' => false],
            // kept in data but hidden — available for future use
            ['accessorKey' => 'customer_name',  'header' => 'CUSTOMER',       'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'account_name',   'header' => 'ACCOUNT',        'isVisible' => false, 'isParameter' => false],
        ];

        $paymentMethods = DB::table('customer_sales_account_payments')
            ->whereNotNull('payment_method')
            ->distinct()
            ->pluck('payment_method')
            ->merge(
                DB::table('customer_account_invoice_payments')
                    ->whereNotNull('payment_method')
                    ->distinct()
                    ->pluck('payment_method')
            )
            ->unique()
            ->sort()
            ->values();

        return inertia('Payment/PaymentIndex', [
            'payments'       => $payments,
            'columns'        => $columns,
            'paymentMethods' => $paymentMethods,
        ]);
    }
}
