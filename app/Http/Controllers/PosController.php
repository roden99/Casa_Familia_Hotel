<?php

namespace App\Http\Controllers;

use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\product;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');

        $query = PosTransaction::with('customer')
            ->orderByDesc('sale_date')
            ->orderByDesc('created_at');

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            if ($column === 'receipt_no') {
                $query->where('receipt_no', 'like', "%{$search}%");
            } elseif ($column === 'payment_method') {
                $query->where('payment_method', 'like', "%{$search}%");
            }
        }

        $transactions = $query->paginate(15)->through(function ($tx) {
            $customerName = '—';
            if ($tx->customer) {
                $c = $tx->customer;
                $customerName = $c->is_drugstore
                    ? strtoupper($c->company)
                    : trim(strtoupper($c->last_name) . ', ' . strtoupper($c->first_name));
            }

            return [
                'id'             => $tx->id,
                'receipt_no'     => $tx->receipt_no ?? '—',
                'sale_date'      => Carbon::parse($tx->sale_date)->format('m-d-Y'),
                'customer_name'  => $customerName,
                'payment_method' => strtoupper($tx->payment_method),
                'notes'          => $tx->notes ?? '',
            ];
        });

        $columns = [
            ['accessorKey' => 'id',             'header' => 'ID',             'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'receipt_no',      'header' => 'RECEIPT NO.',    'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'sale_date',       'header' => 'DATE',           'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'customer_name',   'header' => 'CUSTOMER',       'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'payment_method',  'header' => 'PAYMENT',        'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'notes',           'header' => 'NOTES',          'isVisible' => false, 'isParameter' => false],
        ];

        return inertia('POS/POSIndex', [
            'transactions' => $transactions,
            'columns'      => $columns,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_date'                        => 'required|date',
            'receipt_no'                       => 'nullable|string|max:255',
            'customer_id'                      => 'nullable|exists:customers,id',
            'payment_method'                   => 'required|string|max:50',
            'notes'                            => 'nullable|string|max:1000',
            'items'                            => 'required|array|min:1',
            'items.*.product_id'               => 'required|exists:products,id',
            'items.*.quantity'                 => 'required|numeric|min:0.0001',
            'items.*.unit_price'               => 'required|numeric|min:0',
            'items.*.discount_percentage'      => 'nullable|numeric|min:0|max:100',
        ]);

        $transaction = PosTransaction::create([
            'receipt_no'     => $validated['receipt_no'] ?? null,
            'sale_date'      => $validated['sale_date'],
            'customer_id'    => $validated['customer_id'] ?? null,
            'payment_method' => $validated['payment_method'],
            'notes'          => $validated['notes'] ?? null,
            'created_by'     => $request->user()->id,
        ]);

        foreach ($validated['items'] as $item) {
            $disc  = $item['discount_percentage'] ?? 0;
            $total = round($item['quantity'] * $item['unit_price'] * (1 - $disc / 100), 2);

            PosTransactionItem::create([
                'pos_transaction_id'  => $transaction->id,
                'product_id'          => $item['product_id'],
                'quantity'            => $item['quantity'],
                'unit_price'          => $item['unit_price'],
                'discount_percentage' => $disc,
                'total_price'         => $total,
                'created_by'          => $request->user()->id,
            ]);

            $product = product::find($item['product_id']);
            if ($product) {
                $product->decrement('pos_qty', $item['quantity']);
            }
        }

        return redirect()->route('pos.index')->with('success', 'POS transaction saved successfully!');
    }

    public function show(string $id)
    {
        $transaction = PosTransaction::with(['items.product.unit', 'items.product.brand', 'items.product.drugform', 'customer'])
            ->findOrFail($id);

        $customerName = '—';
        if ($transaction->customer) {
            $c = $transaction->customer;
            $customerName = $c->is_drugstore
                ? strtoupper($c->company)
                : trim(strtoupper($c->last_name) . ', ' . strtoupper($c->first_name));
        }

        $totalAmount = $transaction->items->sum('total_price');

        return response()->json([
            'transaction' => [
                'id'             => $transaction->id,
                'receipt_no'     => $transaction->receipt_no ?? '—',
                'sale_date'      => Carbon::parse($transaction->sale_date)->format('m-d-Y'),
                'customer_name'  => $customerName,
                'payment_method' => strtoupper($transaction->payment_method),
                'notes'          => $transaction->notes ?? '',
                'total_amount'   => number_format($totalAmount, 2),
            ],
            'items' => $transaction->items->map(function ($item) {
                $product = $item->product;
                $parts   = [$product?->productname];
                if ($product?->drugform) $parts[] = $product->drugform->drugformname;
                if ($product?->unit)     $parts[] = strtolower($product->unit->pos_unit) . ' (pcs)';
                $displayName = implode(' ', array_filter($parts));
                if ($product?->brand) $displayName .= ' (' . $product->brand->brandname . ')';

                return [
                    'id'                  => $item->id,
                    'product_name'        => $displayName ?: ('Product #' . $item->product_id),
                    'quantity'            => $item->quantity,
                    'unit_price'          => $item->unit_price,
                    'discount_percentage' => $item->discount_percentage,
                    'total_price'         => $item->total_price,
                ];
            }),
        ]);
    }

    public function destroy(string $id)
    {
        $transaction = PosTransaction::with('items')->findOrFail($id);

        // Restore pos_qty for each item
        foreach ($transaction->items as $item) {
            $product = product::find($item->product_id);
            if ($product) {
                $product->increment('pos_qty', $item->quantity);
            }
        }

        $transaction->delete();

        return redirect()->route('pos.index')->with('success', 'POS transaction deleted and inventory restored.');
    }
}
