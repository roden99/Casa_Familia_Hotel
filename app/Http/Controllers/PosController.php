<?php

namespace App\Http\Controllers;

use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\PosProductLot;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');

        $query = PosTransaction::with('customer')
            ->withSum('items', 'total_price')
            ->orderByDesc('id');

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
                'sale_date'      => Carbon::parse($tx->sale_date)->format('m-d-Y H:i'),
                'customer_name'  => $customerName,
                'total_amount'   => number_format($tx->items_sum_total_price ?? 0, 2),
                'notes'          => $tx->notes ?? '',
            ];
        });

        $columns = [
            ['accessorKey' => 'id',             'header' => 'ID',             'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'receipt_no',      'header' => 'RECEIPT NO.',    'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'sale_date',       'header' => 'DATE & TIME',     'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'customer_name',   'header' => 'CUSTOMER',       'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'total_amount',    'header' => 'AMOUNT',         'isVisible' => true,  'isParameter' => false],
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
            'receipt_no'                       => 'nullable|string|max:255',
            'customer_id'                      => 'nullable|exists:customers,id',
            'payment_method'                   => 'required|string|max:50',
            'notes'                            => 'nullable|string|max:1000',
            'items'                            => 'required|array|min:1',
            'items.*.lot_id'                   => 'required|exists:pos_product_lots,id',
            'items.*.quantity'                 => 'required|numeric|min:0.0001',
            'items.*.unit_price'               => 'required|numeric|min:0',
            'items.*.discount_percentage'      => 'nullable|numeric|min:0|max:100',
        ]);

        $transaction = PosTransaction::create([
            'receipt_no'     => $validated['receipt_no'] ?? null,
            'sale_date'      => now(),
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
                'pos_product_lot_id'  => $item['lot_id'],
                'quantity'            => $item['quantity'],
                'unit_price'          => $item['unit_price'],
                'discount_percentage' => $disc,
                'total_price'         => $total,
                'created_by'          => $request->user()->id,
            ]);

            $posLot = PosProductLot::with('product')->find($item['lot_id']);
            if ($posLot) {
                $posLot->decrement('quantity', $item['quantity']);
                $posLot->product?->decrement('pos_qty', $item['quantity']);
            }
        }

        return $request->wantsJson()
            ? response()->json(['sale_date' => Carbon::parse($transaction->sale_date)->format('m-d-Y H:i')])
            : redirect()->route('pos.index')->with('success', 'POS transaction saved successfully!');
    }

    public function show(string $id)
    {
        $transaction = PosTransaction::with(['items.posProductLot.product.unit', 'items.posProductLot.product.brand', 'items.posProductLot.product.drugform', 'customer'])
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
                'sale_date'      => Carbon::parse($transaction->sale_date)->format('m-d-Y H:i'),
                'customer_name'  => $customerName,
                'payment_method' => strtoupper($transaction->payment_method),
                'notes'          => $transaction->notes ?? '',
                'total_amount'   => number_format($totalAmount, 2),
            ],
            'items' => $transaction->items->map(function ($item) {
                $lot     = $item->posProductLot;
                $product = $lot?->product;
                $parts   = [$product?->productname];
                if ($product?->drugform) $parts[] = $product->drugform->drugformname;
                if ($product?->unit)     $parts[] = strtolower($product->unit->pos_unit) . ' (pcs)';
                $displayName = implode(' ', array_filter($parts));
                if ($product?->brand) $displayName .= ' (' . $product->brand->brandname . ')';

                return [
                    'id'                  => $item->id,
                    'product_name'        => $displayName ?: ('Lot #' . $item->pos_product_lot_id),
                    'quantity'            => $item->quantity,
                    'unit_price'          => $item->unit_price,
                    'discount_percentage' => $item->discount_percentage,
                    'total_price'         => $item->total_price,
                    'lot_number'          => $lot?->lot_number,
                    'expiration_date'     => $lot ? Carbon::parse($lot->expiration_date)->format('m-d-Y') : null,
                ];
            }),
        ]);
    }

    public function destroy(string $id)
    {
        $transaction = PosTransaction::with(['items.posProductLot'])->findOrFail($id);

        // Restore pos_product_lot qty and the product's pos_qty through the lot
        foreach ($transaction->items as $item) {
            if ($item->posProductLot) {
                $item->posProductLot->increment('quantity', $item->quantity);
                $item->posProductLot->product?->increment('pos_qty', $item->quantity);
            }
        }

        $transaction->delete();

        return redirect()->route('pos.index')->with('success', 'POS transaction deleted and inventory restored.');
    }
}
