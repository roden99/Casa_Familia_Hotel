<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $csaPayments = DB::table('customer_sales_account_payments')
            ->select('id', DB::raw("'Sales Order' as type"), 'tag_status', 'payment_date', 'amount', 'reference_no', 'payment_method', 'check_date', 'check_number', 'notes');

        $invoicePayments = DB::table('customer_account_invoice_payments')
            ->select('id', DB::raw("'Invoice' as type"), 'tag_status', 'payment_date', 'amount', 'reference_no', 'payment_method', 'check_date', 'check_number', 'notes');
        $payments = $csaPayments->unionAll($invoicePayments)
            ->orderByDesc('payment_date')
            ->get()
            ->map(fn($row) => [
                'id'             => $row->id,
                'type'           => $row->type,
                'tag_status'     => $row->tag_status,
                'payment_date'   => $row->payment_date,
                'payment_method' => $row->payment_method,
                'amount'         => number_format((float) $row->amount, 2),
                'reference_no'   => $row->reference_no,
                'check_number'   => $row->check_number,
                'check_date'     => $row->check_date,
                'notes'          => $row->notes,
            ]);

        $columns = [
            ['accessorKey' => 'id',             'header' => 'ID',         'isVisible' => false],
            ['accessorKey' => 'type',           'header' => 'TYPE',       'isVisible' => true],
            ['accessorKey' => 'payment_date',   'header' => 'DATE',       'isVisible' => true],
            ['accessorKey' => 'payment_method', 'header' => 'METHOD',     'isVisible' => true],
            ['accessorKey' => 'amount',         'header' => 'AMOUNT',     'isVisible' => true],
            ['accessorKey' => 'reference_no',   'header' => 'REFERENCE',  'isVisible' => true],
            ['accessorKey' => 'check_number',   'header' => 'CHECK NO.',  'isVisible' => true],
            ['accessorKey' => 'check_date',     'header' => 'CHECK DATE', 'isVisible' => true],
            ['accessorKey' => 'notes',          'header' => 'NOTES',      'isVisible' => false],
            ['accessorKey' => 'tag_status',     'header' => 'STATUS',     'isVisible' => true],
        ];

        return inertia('Payment/PaymentIndex', [
            'payments' => $payments,
            'columns'  => $columns,
        ]);
    }

    public function details(Request $request, int $id)
    {
        $type = $request->input('type'); // 'Sales Order' or 'Invoice'

        if ($type === 'Sales Order') {
            $payment = DB::table('customer_sales_account_payments as p')
                ->join('customer_sales_account as csa', 'csa.id', '=', 'p.customer_sales_account_id')
                ->join('customers as c', 'c.id', '=', 'csa.customer_id')
                ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
                ->where('p.id', $id)
                ->select(
                    'p.id',
                    'p.amount',
                    'p.payment_date',
                    'p.reference_no',
                    'p.payment_method',
                    'p.check_date',
                    'p.check_number',
                    'p.notes',
                    'p.tag_status',
                    DB::raw("IF(c.is_drugstore, UPPER(c.company), TRIM(CONCAT(UPPER(c.last_name), ', ', UPPER(c.first_name)))) as customer_name"),
                    DB::raw('UPPER(sa.account_name) as account_name')
                )
                ->first();

            if (!$payment) {
                return response()->json(['error' => 'Payment not found.'], 404);
            }

            $applied = DB::table('sales_orders as so')
                ->where('so.payment_id', $id)
                ->select('so.id', 'so.invoice_no', 'so.invoice_date',
                    DB::raw('(SELECT SUM(soi.quantity * soi.unit_price * (1 - IFNULL(soi.discount_percentage,0)/100))
                              FROM sales_order_items soi WHERE soi.sales_order_id = so.id) as amount'))
                ->get()
                ->map(fn($r) => [
                    'label'  => 'SO #' . $r->id . ($r->invoice_no ? ' — ' . $r->invoice_no : ''),
                    'date'   => $r->invoice_date,
                    'amount' => number_format((float) $r->amount, 2),
                ]);
        } else {
            $payment = DB::table('customer_account_invoice_payments as p')
                ->join('customer_account_invoices as i', 'i.id', '=', 'p.customer_account_invoice_id')
                ->join('customer_sales_account as csa', 'csa.id', '=', 'i.customer_sales_account_id')
                ->join('customers as c', 'c.id', '=', 'csa.customer_id')
                ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
                ->where('p.id', $id)
                ->select(
                    'p.id',
                    'p.amount',
                    'p.payment_date',
                    'p.reference_no',
                    'p.payment_method',
                    'p.check_date',
                    'p.check_number',
                    'p.notes',
                    'p.tag_status',
                    'i.reference_no as invoice_reference',
                    'i.invoice_date as invoice_date',
                    'i.amount as invoice_amount',
                    DB::raw("IF(c.is_drugstore, UPPER(c.company), TRIM(CONCAT(UPPER(c.last_name), ', ', UPPER(c.first_name)))) as customer_name"),
                    DB::raw('UPPER(sa.account_name) as account_name')
                )
                ->first();

            if (!$payment) {
                return response()->json(['error' => 'Payment not found.'], 404);
            }

            $applied = collect([[
                'label'  => 'Invoice' . ($payment->invoice_reference ? ' — ' . $payment->invoice_reference : ''),
                'date'   => $payment->invoice_date,
                'amount' => number_format((float) $payment->invoice_amount, 2),
            ]]);
        }

        return response()->json([
            'payment' => [
                'id'             => $payment->id,
                'type'           => $type,
                'customer_name'  => $payment->customer_name,
                'account_name'   => $payment->account_name,
                'payment_date'   => $payment->payment_date,
                'payment_method' => $payment->payment_method,
                'amount'         => number_format((float) $payment->amount, 2),
                'reference_no'   => $payment->reference_no,
                'check_number'   => $payment->check_number,
                'check_date'     => $payment->check_date,
                'notes'          => $payment->notes,
                'tag_status'     => $payment->tag_status,
            ],
            'applied' => $applied,
        ]);
    }
}
