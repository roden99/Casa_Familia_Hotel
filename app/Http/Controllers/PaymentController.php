<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = DB::table('customer_payments')
            ->orderByDesc('payment_date')
            ->get()
            ->map(fn($row) => [
                'id'             => $row->id,
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
        $payment = DB::table('customer_payments')->where('id', $id)->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment not found.'], 404);
        }

        $applied = DB::table('customer_payment_items as cpi')
            ->where('cpi.customer_payment_id', $id)
            ->leftJoin('sales_orders as so', 'so.id', '=', 'cpi.sales_order_id')
            ->leftJoin('customer_account_invoices as i', 'i.id', '=', 'cpi.customer_account_invoice_id')
            ->select(
                'cpi.sub_amount',
                'cpi.sales_order_id',
                'cpi.customer_account_invoice_id',
                'cpi.customer_sales_account_id',
                'so.invoice_no as so_invoice_no',
                'so.invoice_date as so_date',
                'i.reference_no as inv_ref',
                'i.invoice_date as inv_date'
            )
            ->get()
            ->map(fn($row) => [
                'label'  => $row->sales_order_id
                    ? 'SO #' . $row->sales_order_id . ($row->so_invoice_no ? ' — ' . $row->so_invoice_no : '')
                    : ($row->customer_account_invoice_id
                        ? 'INV #' . $row->customer_account_invoice_id . ($row->inv_ref ? ' — ' . $row->inv_ref : '')
                        : 'Direct Account Payment'),
                'date'   => $row->sales_order_id ? $row->so_date
                    : ($row->customer_account_invoice_id ? $row->inv_date : $payment->payment_date),
                'amount' => number_format((float) $row->sub_amount, 2),
            ]);

        return response()->json([
            'payment' => [
                'id'             => $payment->id,
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
