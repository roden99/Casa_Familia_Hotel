<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\supplier;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');

        $query = Delivery::with(['supplier', 'purchaseOrder']);

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            if ($column === 'supplier_name') {
                $query->whereHas('supplier', function ($q) use ($search) {
                    $q->where('company', 'like', "{$search}%");
                });
            } elseif ($column === 'status') {
                $query->where('status', 'like', "{$search}%");
            } else {
                $query->where($column, 'like', "{$search}%");
            }
        }

        $deliveries = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($item) {
            $item->supplier_name = $item->supplier?->company ?? 'N/A';
            $item->po_number = $item->purchase_order_id ? "#PO-{$item->purchase_order_id}" : 'Standalone';
            return $item;
        });

        $columns = [
            ['accessorKey' => 'id', 'header' => 'ID', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'supplier_name', 'header' => 'SUPPLIER', 'isVisible' => true, 'isParameter' => true],
            // ['accessorKey' => 'po_number', 'header' => 'PO NUMBER', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'invoice_no', 'header' => 'INVOICE NO.', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'invoice_date', 'header' => 'INVOICE DATE', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'delivery_date', 'header' => 'DELIVERY DATE', 'isVisible' => true, 'isParameter' => false],

            ['accessorKey' => 'notes', 'header' => 'NOTES', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'created_at', 'header' => 'CREATED AT', 'isVisible' => false, 'isParameter' => false],
        ];

        return inertia('Deliveries/DeliveryIndex', [
            'deliveries' => $deliveries,
            'columns' => $columns,
            'suppliers' => supplier::where('status', true)->orderBy('company')->get(['id', 'company']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_id'              => 'nullable|exists:purchase_orders,id',
            'supplier_id'                    => 'required|exists:suppliers,id',
            'invoice_no'                     => 'required|string|max:255',
            'invoice_date'                   => 'required|date',
            'delivery_date'                  => 'required|date',
            'items'                          => 'required|array|min:1',
            'items.*.product_id'             => 'required|exists:products,id',
            'items.*.quantity_received'      => 'required|integer|min:1',
            'items.*.unit_price'             => 'required|numeric|min:0',
            'items.*.lot_number'             => 'nullable|string|max:100',
            'items.*.expiration_date'        => 'nullable|date',
        ]);

        $validated['created_by'] = $request->user()->id;

        $delivery = Delivery::create(collect($validated)->except('items')->toArray());

        foreach ($validated['items'] as $item) {
            $lotId = null;

            if (!empty($item['lot_number'])) {
                $lotId = \Illuminate\Support\Facades\DB::table('product_lots')->updateOrInsert(
                    [
                        'product_id' => $item['product_id'],
                        'lot_number' => $item['lot_number'],
                    ],
                    [
                        'expiration_date' => $item['expiration_date'] ?? null,
                        'quantity'        => $item['quantity_received'],
                        'created_by'      => $request->user()->id,
                        'updated_by'      => $request->user()->id,
                        'updated_at'      => now(),
                        'created_at'      => now(),
                    ]
                );

                $lotId = \Illuminate\Support\Facades\DB::table('product_lots')
                    ->where('product_id', $item['product_id'])
                    ->where('lot_number', $item['lot_number'])
                    ->value('id');
            }

            $product = \App\Models\product::find($item['product_id']);

            if ($product && !$product->initial_date && !$product->is_inventory && $product->product_qty == 0) {
                $product->update([
                    'initial_date' => $validated['delivery_date'],
                    'product_qty'  => $item['quantity_received'],
                    'is_inventory' => 1,
                ]);
            }

            \App\Models\DeliveryItem::create([
                'delivery_id'       => $delivery->id,
                'product_id'        => $item['product_id'],
                'quantity_received' => $item['quantity_received'],
                'unit_price'        => $item['unit_price'],
                'lot_id'            => $lotId,
                'warehouse_id'      => 1,
                'created_by'        => $request->user()->id,
            ]);

            if ($product && $product->is_inventory) {
                $afterInit = !$product->initial_date
                    || Carbon::parse($validated['delivery_date'])->startOfDay()->gte(Carbon::parse($product->initial_date)->startOfDay());
                if ($afterInit) {
                    $product->increment('product_qty', $item['quantity_received']);
                }
            }
        }

        return redirect()->route('deliveries.index')->with('success', 'Delivery created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $delivery = Delivery::with(['items.product.brand', 'items.product.unit', 'items.product.drugform', 'items.lot'])->findOrFail($id);

        return response()->json([
            'delivery' => $delivery,
            'items' => $delivery->items->map(function ($item) {
                $product = $item->product;
                $parts = [$product?->productname];
                if ($product?->drugform) $parts[] = $product->drugform->drugformname;
                if ($product?->unit)    $parts[] = $product->unit->unit_name;
                $displayName = implode(' ', array_filter($parts));
                if ($product?->brand)   $displayName .= ' (' . $product->brand->brandname . ')';

                return [
                    'id'              => $item->id,
                    'product_id'      => (string) $item->product_id,
                    'product_name'    => $displayName ?: ('Product #' . $item->product_id),
                    'quantity'        => $item->quantity_received,
                    'unit_price'      => $item->unit_price,
                    'lot_id'          => $item->lot_id,
                    'lot_number'      => $item->lot?->lot_number ?? null,
                    'expiration_date' => $item->lot?->expiration_date ?? null,
                ];
            }),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'supplier_id'                    => 'required|exists:suppliers,id',
            'invoice_no'                     => 'required|string|max:255',
            'invoice_date'                   => 'required|date',
            'delivery_date'                  => 'required|date',
            'items'                          => 'required|array|min:1',
            'items.*.product_id'             => 'required|exists:products,id',
            'items.*.quantity_received'      => 'required|integer|min:1',
            'items.*.unit_price'             => 'required|numeric|min:0',
            'items.*.lot_number'             => 'nullable|string|max:100',
            'items.*.expiration_date'        => 'nullable|date',
        ]);

        $delivery = Delivery::with('items')->findOrFail($id);

        // Reverse old item quantities
        foreach ($delivery->items as $oldItem) {
            $product = \App\Models\product::find($oldItem->product_id);
            if ($product && $product->is_inventory) {
                $afterInit = !$product->initial_date
                    || Carbon::parse($delivery->delivery_date)->startOfDay()->gte(Carbon::parse($product->initial_date)->startOfDay());
                if ($afterInit) {
                    $product->decrement('product_qty', $oldItem->quantity_received);
                }
            }
        }

        // Delete old items
        $delivery->items()->delete();

        // Update delivery header
        $delivery->update([
            'supplier_id'   => $validated['supplier_id'],
            'invoice_no'    => $validated['invoice_no'],
            'invoice_date'  => $validated['invoice_date'],
            'delivery_date' => $validated['delivery_date'],
            'updated_by'    => $request->user()->id,
        ]);

        // Insert new items
        foreach ($validated['items'] as $item) {
            $lotId = null;

            if (!empty($item['lot_number'])) {
                \Illuminate\Support\Facades\DB::table('product_lots')->updateOrInsert(
                    [
                        'product_id' => $item['product_id'],
                        'lot_number' => $item['lot_number'],
                    ],
                    [
                        'expiration_date' => $item['expiration_date'] ?? null,
                        'quantity'        => $item['quantity_received'],
                        'created_by'      => $request->user()->id,
                        'updated_by'      => $request->user()->id,
                        'updated_at'      => now(),
                        'created_at'      => now(),
                    ]
                );

                $lotId = \Illuminate\Support\Facades\DB::table('product_lots')
                    ->where('product_id', $item['product_id'])
                    ->where('lot_number', $item['lot_number'])
                    ->value('id');
            }

            $product = \App\Models\product::find($item['product_id']);

            if ($product && !$product->initial_date && !$product->is_inventory && $product->product_qty == 0) {
                $product->update([
                    'initial_date' => $validated['delivery_date'],
                    'product_qty'  => $item['quantity_received'],
                    'is_inventory' => 1,
                ]);
            }

            \App\Models\DeliveryItem::create([
                'delivery_id'       => $delivery->id,
                'product_id'        => $item['product_id'],
                'quantity_received' => $item['quantity_received'],
                'unit_price'        => $item['unit_price'],
                'lot_id'            => $lotId,
                'warehouse_id'      => 1,
                'created_by'        => $request->user()->id,
            ]);

            if ($product && $product->is_inventory) {
                $afterInit = !$product->initial_date
                    || Carbon::parse($validated['delivery_date'])->startOfDay()->gte(Carbon::parse($product->initial_date)->startOfDay());
                if ($afterInit) {
                    $product->increment('product_qty', $item['quantity_received']);
                }
            }
        }

        return redirect()->route('deliveries.index')->with('success', 'Delivery updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $delivery = Delivery::with('items')->findOrFail($id);

        // Restore stock for each item before deleting
        foreach ($delivery->items as $item) {
            $product = \App\Models\product::find($item->product_id);
            if ($product && $product->is_inventory) {
                $docDate = $delivery->delivery_date ?? null;
                $afterInit = !$product->initial_date
                    || ($docDate && Carbon::parse($docDate)->startOfDay()->gte(Carbon::parse($product->initial_date)->startOfDay()));
                if ($afterInit) {
                    $product->decrement('product_qty', $item->quantity_received);
                }
            }
        }

        // Delete items explicitly first, then the delivery
        $delivery->items()->delete();
        $delivery->delete();

        return redirect()->route('deliveries.index')->with('success', 'Delivery deleted successfully!');
    }
}
