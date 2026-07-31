<?php

namespace App\Http\Controllers;

use App\Models\CarryItem;
use App\Models\CarryItemDetail;
use App\Models\product;
use App\Models\ProductLot;
use Illuminate\Http\Request;

class CarryItemController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');

        $query = CarryItemDetail::with(['carryItem.salesAgent', 'product.brand', 'product.unit', 'product.drugform', 'lot']);

        if (!empty($search) && !empty($column)) {
            if ($column === 'sales_agent_name') {
                $query->whereHas('carryItem.salesAgent', fn($q) => $q->where('name', 'like', "%{$search}%"));
            } elseif ($column === 'product_name') {
                $query->whereHas('product', fn($q) => $q->where('productname', 'like', "%{$search}%"));
            }
        }

        $carryItems = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($detail) {
            $product  = $detail->product;
            $parts    = [$product?->productname];
            if ($product?->drugform) $parts[] = $product->drugform->drugformname;
            if ($product?->unit)     $parts[] = $product->unit->unit_name;
            $displayName = implode(' ', array_filter($parts));
            if ($product?->brand)    $displayName .= ' (' . $product->brand->brandname . ')';

            $detail->product_name      = $displayName ?: ('Product #' . $detail->product_id);
            $detail->sales_agent_name  = $detail->carryItem?->salesAgent?->name ?? 'N/A';
            $detail->carry_date        = $detail->carryItem?->carry_date;
            $detail->lot_number        = $detail->lot?->lot_number ?? '—';
            $detail->expiry_date       = $detail->lot?->expiration_date ?? '—';
            return $detail;
        });

        $columns = [
            ['accessorKey' => 'id',              'header' => 'ID',          'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'sales_agent_name', 'header' => 'SALES AGENT', 'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'product_name',    'header' => 'PRODUCT',     'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'lot_number',      'header' => 'LOT NO.',     'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'expiry_date',     'header' => 'EXPIRY',      'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'quantity',        'header' => 'QTY',         'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'carry_date',      'header' => 'CARRY DATE',  'isVisible' => true,  'isParameter' => false],
        ];

        return inertia('CarryItems/CarryItemIndex', [
            'carryItems' => $carryItems,
            'columns'    => $columns,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_agent_id'         => 'required|exists:sales_agents,id',
            'carry_date'             => 'required|date',
            'notes'                  => 'nullable|string|max:500',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.lot_id'         => 'nullable|exists:product_lots,id',
            'items.*.quantity'       => 'required|numeric|min:0.0001',
        ]);

        $carryItem = CarryItem::create([
            'sales_agent_id' => $validated['sales_agent_id'],
            'carry_date'     => $validated['carry_date'],
            'notes'          => $validated['notes'] ?? null,
            'created_by'     => $request->user()->id,
        ]);

        foreach ($validated['items'] as $item) {
            CarryItemDetail::create([
                'carry_item_id' => $carryItem->id,
                'product_id'    => $item['product_id'],
                'lot_id'        => $item['lot_id'] ?? null,
                'quantity'      => $item['quantity'],
                'created_by'    => $request->user()->id,
            ]);

            $product = product::find($item['product_id']);
            if ($product) {
                $product->decrement('product_qty', $item['quantity']);
            }

            if (!empty($item['lot_id'])) {
                ProductLot::where('id', $item['lot_id'])->decrement('quantity', $item['quantity']);
            }
        }

        return redirect()->route('carry-items.index')->with('success', 'Carry items created successfully!');
    }

    public function show(string $id)
    {
        $carryItem = CarryItem::with(['salesAgent', 'details.product.brand', 'details.product.unit', 'details.product.drugform'])->findOrFail($id);

        return response()->json([
            'carryItem' => $carryItem,
            'items'     => $carryItem->details->map(function ($detail) {
                $product = $detail->product;
                $parts   = [$product?->productname];
                if ($product?->drugform) $parts[] = $product->drugform->drugformname;
                if ($product?->unit)     $parts[] = $product->unit->unit_name;
                $displayName = implode(' ', array_filter($parts));
                if ($product?->brand)    $displayName .= ' (' . $product->brand->brandname . ')';

                return [
                    'id'           => $detail->id,
                    'product_id'   => $detail->product_id,
                    'product_name' => $displayName ?: ('Product #' . $detail->product_id),
                    'quantity'     => $detail->quantity,
                ];
            }),
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $carryItem = CarryItem::with('details')->findOrFail($id);

        foreach ($carryItem->details as $detail) {
            $product = product::find($detail->product_id);
            if ($product) {
                $product->increment('product_qty', $detail->quantity);
            }
            if ($detail->lot_id) {
                ProductLot::where('id', $detail->lot_id)->increment('quantity', $detail->quantity);
            }
        }

        $carryItem->delete();
    }

    public function returnDetail(Request $request, string $detailId)
    {
        $detail = CarryItemDetail::findOrFail($detailId);

        $product = product::find($detail->product_id);
        if ($product) {
            $product->increment('product_qty', $detail->quantity);
        }

        if ($detail->lot_id) {
            ProductLot::where('id', $detail->lot_id)->increment('quantity', $detail->quantity);
        }

        $detail->delete();

        return redirect()->route('carry-items.index')->with('success', 'Item returned to inventory.');
    }
}
