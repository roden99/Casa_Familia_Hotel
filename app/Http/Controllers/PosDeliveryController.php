<?php

namespace App\Http\Controllers;

use App\Models\PosDelivery;
use App\Models\PosDeliveryItem;
use App\Models\PosProductLot;
use App\Models\product;
use App\Models\supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PosDeliveryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');

        $query = PosDelivery::with(['supplier', 'creator']);

        if (!empty($search) && !empty($column)) {
            if ($column === 'supplier_name') {
                $query->whereHas('supplier', fn($q) => $q->where('company', 'like', "%{$search}%"));
            } else {
                $query->where($column, 'like', "%{$search}%");
            }
        }

        $deliveries = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($d) {
            $d->supplier_name  = $d->supplier?->company ?? '—';
            $d->items_count    = $d->items()->count();
            $d->created_by_name = $d->creator?->name ?? 'N/A';
            return $d;
        });

        $columns = [
            ['accessorKey' => 'id',              'header' => 'ID',            'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'supplier_name',   'header' => 'SUPPLIER',      'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'invoice_no',      'header' => 'INVOICE NO.',   'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'delivery_date',   'header' => 'DELIVERY DATE', 'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'items_count',     'header' => 'ITEMS',         'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'notes',           'header' => 'NOTES',         'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'created_by_name', 'header' => 'CREATED BY',   'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'created_at',      'header' => 'CREATED AT',   'isVisible' => false, 'isParameter' => false],
        ];

        return inertia('PosDelivery/PosDeliveryIndex', [
            'deliveries' => $deliveries,
            'columns'    => $columns,
            'suppliers'  => supplier::where('status', 'active')->orderBy('company')->get(['id', 'company']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id'               => 'nullable|exists:suppliers,id',
            'invoice_no'                => 'nullable|string|max:255',
            'delivery_date'             => 'required|date',
            'notes'                     => 'nullable|string|max:1000',
            'items'                     => 'required|array|min:1',
            'items.*.product_id'        => 'required|exists:products,id',
            'items.*.lot_number'        => 'required|string|max:255',
            'items.*.expiration_date'   => 'nullable|date',
            'items.*.quantity'          => 'required|numeric|min:0.0001',
            'items.*.cost'              => 'nullable|numeric|min:0',
            'items.*.selling_price'     => 'nullable|numeric|min:0',
        ]);

        $delivery = PosDelivery::create([
            'supplier_id'   => $validated['supplier_id'] ?? null,
            'invoice_no'    => $validated['invoice_no'] ?? null,
            'delivery_date' => $validated['delivery_date'],
            'notes'         => $validated['notes'] ?? null,
            'created_by'    => $request->user()->id,
        ]);

        foreach ($validated['items'] as $item) {
            $posLot = PosProductLot::firstOrNew([
                'product_id' => $item['product_id'],
                'lot_number' => $item['lot_number'],
            ]);
            if (!$posLot->exists) {
                $posLot->expiration_date = $item['expiration_date'] ?? null;
                $posLot->quantity        = 0;
                $posLot->cost            = 0;
            }
            $posLot->quantity  += $item['quantity'];
            if (array_key_exists('cost', $item) && $item['cost'] !== null)           $posLot->cost          = $item['cost'];
            if (array_key_exists('selling_price', $item) && $item['selling_price'] !== null) $posLot->selling_price = $item['selling_price'];
            $posLot->updated_by = $request->user()->id;
            $posLot->save();

            PosDeliveryItem::create([
                'pos_delivery_id'    => $delivery->id,
                'product_id'         => $item['product_id'],
                'pos_product_lot_id' => $posLot->id,
                'quantity'           => $item['quantity'],
                'cost'               => $item['cost'] ?? null,
                'selling_price'      => $item['selling_price'] ?? null,
                'created_by'         => $request->user()->id,
            ]);

            $product = product::find($item['product_id']);
            if ($product) {
                $product->increment('pos_qty', $item['quantity']);
                if (!$product->initial_pos_date) {
                    $product->update([
                        'initial_pos_date' => now()->startOfDay(),
                        'initial_pos_qty'  => $item['quantity'],
                        'updated_by'       => $request->user()->id,
                    ]);
                }
            }
        }

        return redirect()->route('pos-deliveries.index')->with('success', 'POS delivery recorded successfully!');
    }

    public function show(string $id)
    {
        $delivery = PosDelivery::with([
            'supplier',
            'items.product.brand',
            'items.product.unit',
            'items.product.drugform',
            'items.posProductLot',
        ])->findOrFail($id);

        return response()->json([
            'delivery' => [
                'id'            => $delivery->id,
                'supplier_name' => $delivery->supplier?->company ?? '—',
                'invoice_no'    => $delivery->invoice_no ?? '—',
                'delivery_date' => $delivery->delivery_date,
                'notes'         => $delivery->notes ?? '',
            ],
            'items' => $delivery->items->map(function ($item) {
                $product = $item->product;
                $parts   = [$product?->productname];
                if ($product?->drugform) $parts[] = $product->drugform->drugformname;
                if ($product?->unit)     $parts[] = strtolower($product->unit->pos_unit) . ' (pcs)';
                $displayName = implode(' ', array_filter($parts));
                if ($product?->brand)   $displayName .= ' (' . $product->brand->brandname . ')';

                $lot = $item->posProductLot;
                return [
                    'id'              => $item->id,
                    'product_name'    => $displayName ?: ('Product #' . $item->product_id),
                    'lot_number'      => $lot?->lot_number ?? '—',
                    'expiration_date' => $lot?->expiration_date
                        ? Carbon::parse($lot->expiration_date)->format('m-d-Y')
                        : '—',
                    'quantity'        => (float) $item->quantity,
                    'cost'            => $item->cost !== null ? (float) $item->cost : null,
                    'selling_price'   => $item->selling_price !== null ? (float) $item->selling_price : null,
                ];
            }),
        ]);
    }

    public function destroy(string $id)
    {
        $delivery = PosDelivery::with('items.posProductLot')->findOrFail($id);

        foreach ($delivery->items as $item) {
            $product = product::find($item->product_id);
            if ($product) {
                $product->decrement('pos_qty', $item->quantity);
            }
            if ($item->posProductLot) {
                $item->posProductLot->decrement('quantity', $item->quantity);
            }
        }

        $delivery->items()->delete();
        $delivery->delete();

        return redirect()->route('pos-deliveries.index')->with('success', 'POS delivery deleted and inventory restored.');
    }
}
