<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');

        // ── Sales Orders ──────────────────────────────────────────────────────
        $soQuery = DB::table('sales_orders as so')
            ->join('customer_sales_account as csa', 'csa.id', '=', 'so.customer_sales_account_id')
            ->join('customers as c', 'c.id', '=', 'csa.customer_id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->leftJoin('customer_sales_account_payments as pmt', 'pmt.id', '=', 'so.payment_id')
            ->select(
                'so.id',
                'so.invoice_no',
                'so.invoice_date',
                'so.terms',
                'so.payment_id',
                'so.customer_sales_account_id',
                'c.company',
                'c.first_name',
                'c.last_name',
                'c.is_drugstore',
                'sa.account_name',
                'pmt.amount as pmt_amount',
                'pmt.payment_date as pmt_date',
                'pmt.payment_method as pmt_method',
                'pmt.reference_no as pmt_reference',
                'pmt.check_number as pmt_check_number',
                'pmt.check_date as pmt_check_date',
                'pmt.notes as pmt_notes'
            );

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            if ($column === 'customer_name') {
                $soQuery->where(function ($q) use ($search) {
                    $q->where('c.last_name', 'like', "{$search}%")
                        ->orWhere('c.company', 'like', "{$search}%");
                });
            } elseif ($column === 'account_name') {
                $soQuery->where('sa.account_name', 'like', "{$search}%");
            } elseif ($column === 'invoice_no') {
                $soQuery->where('so.invoice_no', 'like', "{$search}%");
            }
        }

        $soRows = $soQuery->orderByDesc('so.invoice_date')->get()->map(function ($item) {
            $customerName = $item->is_drugstore
                ? strtoupper($item->company)
                : trim(strtoupper($item->last_name) . ', ' . strtoupper($item->first_name));

            $total = DB::table('sales_order_items')
                ->where('sales_order_id', $item->id)
                ->sum(DB::raw('quantity * unit_price * (1 - IFNULL(discount_percentage, 0) / 100)'));

            return [
                'id'                        => $item->id,
                'entry_type'                => 'SO',
                'customer_sales_account_id' => $item->customer_sales_account_id,
                'customer_name'             => $customerName,
                'account_name'              => strtoupper($item->account_name),
                'invoice_no'                => $item->invoice_no ?? '',
                'invoice_date'              => $item->invoice_date ? Carbon::parse($item->invoice_date)->format('m-d-Y') : null,
                'due_date'                  => ($item->invoice_date && $item->terms)
                    ? Carbon::parse($item->invoice_date)->addDays((int) $item->terms)->format('m-d-Y')
                    : null,
                'terms'                     => $item->terms !== null ? (int) $item->terms : null,
                'total_amount'              => number_format((float) $total, 2, '.', ','),
                'payment_id'                => $item->payment_id ?? null,
                'payment_status'            => $item->payment_id ? 'Paid' : 'Unpaid',
                'payment_details'           => $item->payment_id ? [
                    'amount'       => number_format((float) $item->pmt_amount, 2, '.', ','),
                    'date'         => $item->pmt_date ? Carbon::parse($item->pmt_date)->format('m-d-Y') : null,
                    'method'       => $item->pmt_method ?? null,
                    'reference'    => $item->pmt_reference ?? null,
                    'check_number' => $item->pmt_check_number ?? null,
                    'check_date'   => $item->pmt_check_date ? Carbon::parse($item->pmt_check_date)->format('m-d-Y') : null,
                    'notes'        => $item->pmt_notes ?? null,
                ] : null,
            ];
        });

        // ── Manual Invoices ────────────────────────────────────────────────────
        $invQuery = DB::table('customer_account_invoices as i')
            ->join('customer_sales_account as csa', 'csa.id', '=', 'i.customer_sales_account_id')
            ->join('customers as c', 'c.id', '=', 'csa.customer_id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->leftJoin('customer_sales_account_payments as pmt', 'pmt.id', '=', 'i.payment_id')
            ->select(
                'i.id',
                'i.reference_no',
                'i.invoice_date',
                'i.amount',
                'i.payment_id',
                'i.customer_sales_account_id',
                'c.company',
                'c.first_name',
                'c.last_name',
                'c.is_drugstore',
                'sa.account_name',
                'pmt.amount as pmt_amount',
                'pmt.payment_date as pmt_date',
                'pmt.payment_method as pmt_method',
                'pmt.reference_no as pmt_reference',
                'pmt.check_number as pmt_check_number',
                'pmt.check_date as pmt_check_date',
                'pmt.notes as pmt_notes'
            );

        if (!empty($search) && strlen($search) >= 3) {
            if ($column === 'customer_name') {
                $invQuery->where(function ($q) use ($search) {
                    $q->where('c.last_name', 'like', "{$search}%")
                        ->orWhere('c.company', 'like', "{$search}%");
                });
            } elseif ($column === 'account_name') {
                $invQuery->where('sa.account_name', 'like', "{$search}%");
            } elseif ($column === 'invoice_no') {
                $invQuery->where('i.reference_no', 'like', "{$search}%");
            }
        }

        $invRows = $invQuery->orderByDesc('i.invoice_date')->get()->map(function ($item) {
            $customerName = $item->is_drugstore
                ? strtoupper($item->company)
                : trim(strtoupper($item->last_name) . ', ' . strtoupper($item->first_name));

            return [
                'id'                        => $item->id,
                'entry_type'                => 'INV',
                'customer_sales_account_id' => $item->customer_sales_account_id,
                'customer_name'             => $customerName,
                'account_name'              => strtoupper($item->account_name),
                'invoice_no'                => $item->reference_no ?? '',
                'invoice_date'              => $item->invoice_date ? Carbon::parse($item->invoice_date)->format('m-d-Y') : null,
                'due_date'                  => null,
                'terms'                     => null,
                'total_amount'              => number_format((float) $item->amount, 2, '.', ','),
                'payment_id'                => $item->payment_id ?? null,
                'payment_status'            => $item->payment_id ? 'Paid' : 'Unpaid',
                'payment_details'           => $item->payment_id ? [
                    'amount'       => number_format((float) $item->pmt_amount, 2, '.', ','),
                    'date'         => $item->pmt_date ? Carbon::parse($item->pmt_date)->format('m-d-Y') : null,
                    'method'       => $item->pmt_method ?? null,
                    'reference'    => $item->pmt_reference ?? null,
                    'check_number' => $item->pmt_check_number ?? null,
                    'check_date'   => $item->pmt_check_date ? Carbon::parse($item->pmt_check_date)->format('m-d-Y') : null,
                    'notes'        => $item->pmt_notes ?? null,
                ] : null,
            ];
        });

        // ── Merge, sort, paginate ─────────────────────────────────────────────
        $combined = $soRows->concat($invRows)
            ->sortByDesc(fn($r) => $r['invoice_date'] ?? '')
            ->values();

        $perPage = 15;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $paged = $combined->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $orders = new \Illuminate\Pagination\LengthAwarePaginator(
            $paged,
            $combined->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        $columns = [
            ['accessorKey' => 'id',             'header' => 'ID',           'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'entry_type',      'header' => 'TYPE',         'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'account_name',    'header' => 'ACCOUNT',      'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'customer_name',   'header' => 'CUSTOMER',     'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'invoice_no',      'header' => 'INVOICE NO.',  'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'invoice_date',    'header' => 'INVOICE DATE', 'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'due_date',        'header' => 'DUE DATE',     'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'terms',           'header' => 'TERMS',        'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'total_amount',    'header' => 'TOTAL',        'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'payment_status',  'header' => 'STATUS',       'isVisible' => true,  'isParameter' => false],
        ];

        return inertia('SalesOrders/SalesOrderIndex', [
            'orders'  => $orders,
            'columns' => $columns,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_sales_account_id'     => 'required|exists:customer_sales_account,id',
            'invoice_no'                    => 'nullable|string|max:255',
            'invoice_date'                  => 'nullable|date',
            // 'delivery_date'              => 'nullable|date',
            // 'discount_percentage'           => 'nullable|numeric|min:0|max:100',
            'terms'                         => 'nullable|integer|min:0',
            'items'                         => 'required|array|min:1',
            'items.*.product_id'            => 'required|exists:products,id',
            'items.*.quantity'              => 'required|integer|min:1',
            'items.*.unit_price'            => 'required|numeric|min:0',
            'items.*.discount_percentage'   => 'nullable|numeric|min:0|max:100',
        ]);

        $validated['created_by'] = $request->user()->id;

        $order = SalesOrder::create(collect($validated)->except('items')->toArray());

        foreach ($validated['items'] as $item) {
            $disc = $item['discount_percentage'] ?? 0;
            $totalPrice = round($item['quantity'] * $item['unit_price'] * (1 - $disc / 100), 2);

            SalesOrderItem::create([
                'sales_order_id'      => $order->id,
                'product_id'          => $item['product_id'],
                'quantity'            => $item['quantity'],
                'unit_price'          => $item['unit_price'],
                'discount_percentage' => $disc,
                'total_price'         => $totalPrice,
                'created_by'          => $request->user()->id,
            ]);

            $product = product::find($item['product_id']);
            if ($product && $product->is_inventory) {
                $docDate = $validated['invoice_date'] ?? null;
                $afterInit = !$product->initial_date
                    || ($docDate && Carbon::parse($docDate)->startOfDay()->gte(Carbon::parse($product->initial_date)->startOfDay()));
                if ($afterInit) {
                    $product->decrement('product_qty', $item['quantity']);
                }
            }
        }

        return redirect()->route('sales-orders.index')->with('success', 'Sales order created successfully!');
    }

    public function show(string $id)
    {
        $order = SalesOrder::with(['items.product.brand', 'items.product.unit', 'items.product.drugform'])->findOrFail($id);

        return response()->json([
            'order' => $order,
            'items' => $order->items->map(function ($item) {
                $product = $item->product;
                $parts = [$product?->productname];
                if ($product?->drugform) $parts[] = $product->drugform->drugformname;
                if ($product?->unit)     $parts[] = $product->unit->unit_name;
                $displayName = implode(' ', array_filter($parts));
                if ($product?->brand)    $displayName .= ' (' . $product->brand->brandname . ')';

                return [
                    'id'                  => $item->id,
                    'product_id'          => (string) $item->product_id,
                    'product_name'        => $displayName ?: ('Product #' . $item->product_id),
                    'quantity'            => $item->quantity,
                    'unit_price'          => $item->unit_price,
                    'discount_percentage' => $item->discount_percentage,
                ];
            }),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'customer_sales_account_id'     => 'required|exists:customer_sales_account,id',
            'invoice_no'                    => 'nullable|string|max:255',
            'invoice_date'                  => 'nullable|date',
            // 'delivery_date'              => 'nullable|date',
            // 'discount_percentage'           => 'nullable|numeric|min:0|max:100',
            'terms'                         => 'nullable|integer|min:0',
            'items'                         => 'required|array|min:1',
            'items.*.product_id'            => 'required|exists:products,id',
            'items.*.quantity'              => 'required|integer|min:1',
            'items.*.unit_price'            => 'required|numeric|min:0',
            'items.*.discount_percentage'   => 'nullable|numeric|min:0|max:100',
        ]);

        $order = SalesOrder::with('items')->findOrFail($id);

        // Reverse old item quantities back to stock
        foreach ($order->items as $oldItem) {
            $product = product::find($oldItem->product_id);
            if ($product && $product->is_inventory) {
                $oldDocDate = $order->invoice_date ?? null;
                $afterInit = !$product->initial_date
                    || ($oldDocDate && Carbon::parse($oldDocDate)->startOfDay()->gte(Carbon::parse($product->initial_date)->startOfDay()));
                if ($afterInit) {
                    $product->increment('product_qty', $oldItem->quantity);
                }
            }
        }

        $order->items()->delete();

        $order->update([
            'customer_sales_account_id' => $validated['customer_sales_account_id'],
            'invoice_no'                => $validated['invoice_no'],
            'invoice_date'              => $validated['invoice_date'],
            // 'delivery_date'          => ...,
            // 'discount_percentage'       => $validated['discount_percentage'] ?? 0,
            'terms'                     => $validated['terms'] ?? null,
            'updated_by'                => $request->user()->id,
        ]);

        foreach ($validated['items'] as $item) {
            $disc = $item['discount_percentage'] ?? 0;
            $totalPrice = round($item['quantity'] * $item['unit_price'] * (1 - $disc / 100), 2);

            SalesOrderItem::create([
                'sales_order_id'      => $order->id,
                'product_id'          => $item['product_id'],
                'quantity'            => $item['quantity'],
                'unit_price'          => $item['unit_price'],
                'discount_percentage' => $disc,
                'total_price'         => $totalPrice,
                'created_by'          => $request->user()->id,
            ]);

            $product = product::find($item['product_id']);
            if ($product && $product->is_inventory) {
                $docDate = $validated['invoice_date'] ?? null;
                $afterInit = !$product->initial_date
                    || ($docDate && Carbon::parse($docDate)->startOfDay()->gte(Carbon::parse($product->initial_date)->startOfDay()));
                if ($afterInit) {
                    $product->decrement('product_qty', $item['quantity']);
                }
            }
        }

        return redirect()->route('sales-orders.index')->with('success', 'Sales order updated successfully!');
    }

    public function destroy(string $id)
    {
        $order = SalesOrder::with('items')->findOrFail($id);

        // Restore stock for each item before deleting
        foreach ($order->items as $item) {
            $product = product::find($item->product_id);
            if ($product && $product->is_inventory) {
                $docDate = $order->invoice_date ?? null;
                $afterInit = !$product->initial_date
                    || ($docDate && Carbon::parse($docDate)->startOfDay()->gte(Carbon::parse($product->initial_date)->startOfDay()));
                if ($afterInit) {
                    $product->increment('product_qty', $item->quantity);
                }
            }
        }

        // Delete items explicitly first, then the order
        $order->items()->delete();
        $order->delete();

        return redirect()->route('sales-orders.index')->with('success', 'Sales order deleted successfully!');
    }
}
