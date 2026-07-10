<?php

namespace App\Http\Controllers;

use App\Models\TransferStock;
use App\Models\TransferStockItem;
use App\Models\product;
use Illuminate\Http\Request;

class TransferStockController extends Controller
{
    public function index(Request $request)
    {
        $search  = $request->input('search');
        $column  = $request->input('column');

        $query = TransferStock::with(['creator']);

        if (!empty($search) && !empty($column)) {
            $query->where($column, 'like', "%{$search}%");
        }

        $transfers = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($item) {
            $item->created_by_name = $item->creator?->name ?? 'N/A';
            $item->items_count     = $item->items()->count();
            return $item;
        });

        $columns = [
            ['accessorKey' => 'id',              'header' => 'ID',            'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'transfer_date',   'header' => 'TRANSFER DATE', 'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'items_count',     'header' => 'ITEMS',         'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'notes',           'header' => 'NOTES',         'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'created_by_name', 'header' => 'CREATED BY',   'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'created_at',      'header' => 'CREATED AT',   'isVisible' => false, 'isParameter' => false],
        ];

        return inertia('TransferStock/TransferStockIndex', [
            'transfers' => $transfers,
            'columns'   => $columns,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transfer_date'          => 'required|date',
            'notes'                  => 'nullable|string|max:500',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|numeric|min:0.0001',
            'items.*.multiplier'     => 'required|numeric|min:0',
        ]);

        $transfer = TransferStock::create([
            'transfer_date' => $validated['transfer_date'],
            'notes'         => $validated['notes'] ?? null,
            'created_by'    => $request->user()->id,
        ]);

        foreach ($validated['items'] as $item) {
            TransferStockItem::create([
                'transfer_stock_id' => $transfer->id,
                'product_id'        => $item['product_id'],
                'quantity'          => $item['quantity'],
                'multiplier'        => $item['multiplier'],
                'created_by'        => $request->user()->id,
            ]);

            $product = product::find($item['product_id']);
            if ($product) {
                // Subtract from warehouse product_qty
                $product->decrement('product_qty', $item['quantity']);

                // Add to pos_qty using multiplier
                $product->increment('pos_qty', $item['quantity'] * $item['multiplier']);
            }
        }

        return redirect()->route('transfer-stocks.index')->with('success', 'Transfer created successfully!');
    }

    public function show(string $id)
    {
        $transfer = TransferStock::with(['items.product.brand', 'items.product.unit', 'items.product.drugform'])->findOrFail($id);

        return response()->json([
            'transfer' => $transfer,
            'items'    => $transfer->items->map(function ($item) {
                $product = $item->product;
                $parts   = [$product?->productname];
                if ($product?->drugform) $parts[] = $product->drugform->drugformname;
                if ($product?->unit)     $parts[] = $product->unit->unit_name;
                $displayName = implode(' ', array_filter($parts));
                if ($product?->brand)    $displayName .= ' (' . $product->brand->brandname . ')';

                return [
                    'id'         => $item->id,
                    'product_id' => (string) $item->product_id,
                    'product_name' => $displayName ?: ('Product #' . $item->product_id),
                    'quantity'   => $item->quantity,
                    'multiplier' => $item->multiplier,
                    'pos_qty_added' => $item->quantity * $item->multiplier,
                ];
            }),
        ]);
    }

    public function destroy(string $id)
    {
        $transfer = TransferStock::with('items')->findOrFail($id);

        foreach ($transfer->items as $item) {
            $product = \App\Models\product::find($item->product_id);
            if ($product) {
                $product->increment('product_qty', $item->quantity);
                $product->decrement('pos_qty', $item->quantity * $item->multiplier);
            }
        }

        $transfer->items()->delete();
        $transfer->delete();

        return redirect()->route('transfer-stocks.index')->with('success', 'Transfer deleted successfully!');
    }
}
