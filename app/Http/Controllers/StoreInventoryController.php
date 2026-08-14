<?php

namespace App\Http\Controllers;

use App\Models\product;
use App\Models\brand;
use App\Models\ProductUnit;
use App\Models\strength;
use App\Models\drugform;
use App\Models\PosDeliveryItem;
use App\Models\TransferStockItem;
use App\Models\PosTransactionItem;
use App\Models\PosProductLot;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StoreInventoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');
        $type   = $request->input('type');

        $query = product::with(['brand', 'unit', 'drugform', 'productType'])
            ->where('status', true)
            ->whereNotNull('initial_pos_date');

        if ($type === 'generic') {
            $query->where('isgeneric', true);
        } elseif ($type === 'branded') {
            $query->where('isgeneric', false);
        }

        if (!empty($search) && !empty($column)) {
            if ($column === 'brand_name') {
                $query->whereHas('brand', function ($q) use ($search) {
                    $q->where('brandname', 'like', "%{$search}%");
                });
            } elseif ($column === 'type_name') {
                $query->whereHas('productType', function ($q) use ($search) {
                    $q->where('type_name', 'like', "%{$search}%");
                });
            } else {
                $query->where($column, 'like', "%{$search}%");
            }
        }

        $products = $query->orderBy('productname')->paginate(15)->through(function ($product) {
            $product->status_text  = $product->status ? 'Active' : 'Inactive';
            $product->generic_text = $product->isgeneric ? 'Generic' : 'Branded';
            $product->brand_name   = $product->brand?->brandname ?? 'N/A';
            $product->type_name    = $product->productType?->type_name ?? 'N/A';
            $product->pos_qty      = $product->is_inventory ? ((float) ($product->pos_qty ?? 0) + 0) : '-';
            $product->pos_unit     = $product->unit?->pos_unit ?? '';

            // Build display name: productname drugform pos_unit (pcs) (brand)
            $parts = [$product->productname];
            if ($product->drugform) {
                $parts[] = $product->drugform->drugformname;
            }
            if ($product->unit) {
                $parts[] = strtolower($product->unit->pos_unit) . ' (pcs)';
            }
            $displayName = implode(' ', $parts);
            if ($product->brand) {
                $displayName .= ' (' . $product->brand->brandname . ')';
            }
            $product->display_name = $displayName;

            return $product;
        });

        $columns = [
            ['accessorKey' => 'id',            'header' => 'ID',           'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'generic_text',  'header' => 'TYPE',         'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'type_name',     'header' => 'CATEGORY',     'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'display_name',  'header' => 'PRODUCT NAME', 'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'pos_qty',            'header' => 'POS QTY',       'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'pos_selling_price',   'header' => 'SELLING PRICE', 'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'productname',   'header' => 'PRODUCT NAME', 'isVisible' => false, 'isParameter' => true],
            ['accessorKey' => 'brand_name',    'header' => 'BRAND',        'isVisible' => false, 'isParameter' => true],
            ['accessorKey' => 'status_text',   'header' => 'STATUS',       'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'created_at',    'header' => 'CREATED AT',   'isVisible' => false, 'isParameter' => false],
        ];

        return inertia('StoreInventory/StoreInventoryIndex', [
            'products'     => $products,
            'columns'      => $columns,
            'brands'       => brand::where('status', true)->orderBy('brandname')->get(['id', 'brandname']),
            'productUnits' => ProductUnit::where('status', true)->orderBy('unit_name')->get(['id', 'unit_name']),
            'strengths'    => strength::where('status', true)->orderBy('strengthname')->get(['id', 'strengthname']),
            'drugforms'    => drugform::where('status', true)->orderBy('drugformname')->get(['id', 'drugformname']),
        ]);
    }

    public function posSellingPrice(Request $request, product $product)
    {
        $validated = $request->validate([
            'pos_selling_price' => 'required|numeric|min:0',
        ]);

        $product->update([
            'pos_selling_price' => $validated['pos_selling_price'],
            'updated_by'        => $request->user()->id,
        ]);

        return redirect()->back()->with('success', 'Selling price updated successfully!');
    }

    public function updatePosQty(Request $request, product $product)
    {
        $validated = $request->validate([
            'pos_qty' => 'required|numeric|min:0',
        ]);

        $product->update([
            'pos_qty'          => $validated['pos_qty'],
            'initial_pos_qty'  => $validated['pos_qty'],
            'initial_pos_date' => now()->startOfDay(),
            'updated_by'       => $request->user()->id,
        ]);

        return redirect()->back()->with('success', 'POS quantity updated successfully!');
    }

    public function initPosProducts(Request $request)
    {
        $search = $request->input('search', '');

        $query = product::with(['brand', 'unit', 'drugform'])
            ->where('status', true)
            ->whereNull('initial_pos_date');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('productname', 'like', "%{$search}%")
                    ->orWhereHas('brand', fn($b) => $b->where('brandname', 'like', "%{$search}%"));
            });
        }

        $products = $query->orderBy('productname')->limit(30)->get()->map(function ($product) {
            $parts = [$product->productname];
            if ($product->drugform) $parts[] = $product->drugform->drugformname;
            if ($product->unit)     $parts[] = $product->unit->pos_unit;
            $displayName = implode(' ', $parts);
            if ($product->brand) $displayName .= ' (' . $product->brand->brandname . ')';

            return [
                'value'             => (string) $product->id,
                'label'             => $displayName,
                'product_qty'       => $product->product_qty ?? 0,
                'pos_unit'          => $product->unit?->pos_unit ?? 'pcs',
                'multiplier'        => $product->unit?->multiplier ?? 1,
                'pos_selling_price' => $product->pos_selling_price ?? '',
            ];
        });

        return response()->json(['products' => $products]);
    }

    public function bulkInitPosQty(Request $request)
    {
        $validated = $request->validate([
            'items'                     => 'required|array|min:1',
            'items.*.product_id'        => 'required|exists:products,id',
            'items.*.pos_qty'           => 'required|numeric|min:0',
            'items.*.pos_selling_price' => 'nullable|numeric|min:0',
            'items.*.lot_number'        => 'nullable|string|max:255',
            'items.*.expiration_date'   => 'nullable|date',
            'items.*.cost'              => 'nullable|numeric|min:0',
        ]);

        // Group by product so pos_qty is the sum of all lot quantities
        $byProduct = collect($validated['items'])->groupBy('product_id');

        foreach ($byProduct as $productId => $items) {
            $totalQty     = $items->sum('pos_qty');
            $sellingPrice = $items->first()['pos_selling_price'] ?? null;

            $update = [
                'pos_qty'          => $totalQty,
                'initial_pos_qty'  => $totalQty,
                'initial_pos_date' => now()->startOfDay(),
                'updated_by'       => $request->user()->id,
            ];
            if ($sellingPrice !== null) {
                $update['pos_selling_price'] = $sellingPrice;
            }
            product::findOrFail($productId)->update($update);

            foreach ($items as $item) {
                if (!empty($item['lot_number']) && !empty($item['expiration_date'])) {
                    PosProductLot::updateOrCreate(
                        ['product_id' => $productId, 'lot_number' => $item['lot_number']],
                        [
                            'expiration_date' => $item['expiration_date'],
                            'quantity'        => $item['pos_qty'],
                            'cost'            => $item['cost'] ?? 0,
                            'updated_by'      => $request->user()->id,
                        ]
                    );
                }
            }
        }

        return redirect()->back()->with('success', 'POS quantities initialized successfully!');
    }

    public function posProducts(Request $request)
    {
        $search = $request->input('search', '');

        $query = PosProductLot::with(['product.brand', 'product.unit', 'product.drugform'])
            ->where('quantity', '>', 0)
            ->whereHas('product', fn($q) => $q->where('status', true));

        if (!empty($search)) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('productname', 'like', "%{$search}%")
                    ->orWhere('product_code', 'like', "%{$search}%")
                    ->orWhereHas('brand', fn($b) => $b->where('brandname', 'like', "%{$search}%"));
            });
        }

        $lots = $query->orderBy('expiration_date')->limit(30)->get()->map(function ($lot) {
            $product = $lot->product;
            $parts = [$product->productname];
            if ($product->drugform) $parts[] = $product->drugform->drugformname;
            if ($product->unit)     $parts[] = strtolower($product->unit->pos_unit) . ' (pcs)';
            $productName = implode(' ', $parts);
            if ($product->brand) $productName .= ' (' . $product->brand->brandname . ')';
            $displayName = $productName . ' — ' . $lot->lot_number . ' (exp: ' . Carbon::parse($lot->expiration_date)->format('m-d-Y') . ')';

            return [
                'value'             => (string) $lot->id,
                'label'             => $displayName,
                'product_name'      => $productName,
                'lot_number'        => $lot->lot_number,
                'expiration_date'   => $lot->expiration_date ? Carbon::parse($lot->expiration_date)->format('m-d-Y') : null,
                'pos_qty'           => (float) $lot->quantity,
                'pos_unit'          => $product->unit?->pos_unit ?? 'pcs',
                'product_code'      => $product->product_code ?? '',
                'pos_selling_price' => $lot->selling_price ?? 0,
            ];
        });

        return response()->json(['products' => $lots]);
    }

    public function posProductLots(product $product)
    {
        $lots = PosProductLot::where('product_id', $product->id)
            ->where('quantity', '>', 0)
            ->orderBy('expiration_date')
            ->get()
            ->map(fn($lot) => [
                'id'              => $lot->id,
                'lot_number'      => $lot->lot_number,
                'expiration_date' => Carbon::parse($lot->expiration_date)->format('m-d-Y'),
                'expiration_raw'  => $lot->expiration_date,
                'quantity'        => (float) $lot->quantity,
                'cost'            => (float) $lot->cost,
                'selling_price'   => $lot->selling_price !== null ? (float) $lot->selling_price : null,
                'is_expired'      => Carbon::parse($lot->expiration_date)->isPast(),
            ]);

        return response()->json(['lots' => $lots]);
    }

    public function storePosProductLot(Request $request, product $product)
    {
        $validated = $request->validate([
            'lot_number'      => 'required|string|max:255',
            'expiration_date' => 'required|date',
            'quantity'        => 'required|numeric|min:0.0001|max:' . ($product->pos_qty ?? PHP_INT_MAX),
            'cost'            => 'nullable|numeric|min:0',
            'selling_price'   => 'nullable|numeric|min:0',
        ]);

        PosProductLot::updateOrCreate(
            ['product_id' => $product->id, 'lot_number' => $validated['lot_number']],
            [
                'expiration_date' => $validated['expiration_date'],
                'quantity'        => $validated['quantity'],
                'cost'            => $validated['cost'] ?? 0,
                'selling_price'   => $validated['selling_price'] ?? null,
                'updated_by'      => $request->user()->id,
            ]
        );

        return response()->json(['message' => 'Lot saved.'], 201);
    }

    public function updatePosProductLot(Request $request, product $product, int $lot)
    {
        $validated = $request->validate([
            'cost'          => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
        ]);

        PosProductLot::where('id', $lot)
            ->where('product_id', $product->id)
            ->update([
                'cost'          => $validated['cost'] ?? 0,
                'selling_price' => $validated['selling_price'] ?? null,
                'updated_by'    => $request->user()->id,
            ]);

        return response()->json(['message' => 'Lot updated.']);
    }

    public function destroyPosProductLot(product $product, int $lot)
    {
        PosProductLot::where('id', $lot)
            ->where('product_id', $product->id)
            ->delete();

        return response()->json(['message' => 'Lot removed.']);
    }

    public function history(product $product)
    {
        $product->load(['unit', 'brand', 'drugform']);

        // Build display name using pos_unit
        $parts = [$product->productname];
        if ($product->drugform) $parts[] = $product->drugform->drugformname;
        $unitLabel = strtolower($product->unit?->pos_unit ?? 'pcs');
        $parts[] = '(' . $unitLabel . ')';
        $displayName = implode(' ', $parts);
        if ($product->brand) $displayName .= ' (' . $product->brand->brandname . ')';

        // Transfer Stock items → IN events
        $initialDate = $product->initial_pos_date
            ? \Carbon\Carbon::parse($product->initial_pos_date)->startOfDay()
            : null;

        $transfers = TransferStockItem::with('transferStock')
            ->where('product_id', $product->id)
            ->get()
            ->map(function ($item) use ($initialDate) {
                $date = Carbon::parse($item->transferStock->transfer_date)->startOfDay();
                return [
                    'date'           => $date,
                    'type'           => 'IN',
                    'reference'      => 'Transfer #' . $item->transfer_stock_id,
                    'party'          => '—',
                    'qty'            => round($item->quantity * $item->multiplier, 4),
                    'qty_raw'        => $item->quantity,
                    'multiplier'     => $item->multiplier,
                    'before_initial' => $initialDate ? $date->lt($initialDate) : false,
                ];
            });

        // Build entries: INITIAL first, then transfers
        $entries = collect();
        if ($initialDate) {
            $entries->push([
                'date'           => $initialDate,
                'type'           => 'INITIAL',
                'reference'      => 'Initial POS Inventory',
                'party'          => '—',
                'qty'            => $product->initial_pos_qty ?? 0,
                'qty_raw'        => $product->initial_pos_qty ?? 0,
                'multiplier'     => '—',
                'before_initial' => false,
            ]);
        }

        // POS Transaction items → OUT events
        $sales = PosTransactionItem::with('posTransaction')
            ->whereHas('posProductLot', fn($q) => $q->where('product_id', $product->id))
            ->get()
            ->map(function ($item) use ($initialDate) {
                $date = Carbon::parse($item->posTransaction->sale_date)->startOfDay();
                return [
                    'date'           => $date,
                    'type'           => 'OUT',
                    'reference'      => 'POS #' . $item->pos_transaction_id,
                    'party'          => '—',
                    'qty'            => round($item->quantity, 4),
                    'qty_raw'        => $item->quantity,
                    'multiplier'     => '—',
                    'before_initial' => $initialDate ? $date->lt($initialDate) : false,
                ];
            });

        // POS Delivery items → IN events
        $posDeliveries = PosDeliveryItem::with('posDelivery.supplier')
            ->where('product_id', $product->id)
            ->get()
            ->map(function ($item) use ($initialDate) {
                $delivery = $item->posDelivery;
                $date = Carbon::parse($delivery->delivery_date)->startOfDay();
                $ref = 'POS Delivery #' . $delivery->id;
                if ($delivery->invoice_no) $ref .= ' (' . $delivery->invoice_no . ')';
                return [
                    'date'           => $date,
                    'type'           => 'IN',
                    'reference'      => $ref,
                    'party'          => $delivery->supplier?->company ?? '—',
                    'qty'            => round($item->quantity, 4),
                    'qty_raw'        => $item->quantity,
                    'multiplier'     => 1,
                    'before_initial' => $initialDate ? $date->lt($initialDate) : false,
                ];
            });

        $entries = $entries->concat($transfers)->concat($sales)->concat($posDeliveries)->sortBy('date')->values();

        $balance = 0;
        $initialReached = false;
        $result = $entries->map(function ($entry) use (&$balance, &$initialReached) {
            if ($entry['type'] === 'INITIAL') {
                $initialReached = true;
                $balance = (float) $entry['qty'];
            } elseif ($initialReached && !$entry['before_initial']) {
                if ($entry['type'] === 'IN')  $balance += $entry['qty'];
                else                          $balance -= $entry['qty'];
            }
            return array_merge($entry, [
                'balance' => round($balance, 4),
                'date'    => Carbon::parse($entry['date'])->format('m-d-Y'),
            ]);
        });

        return response()->json([
            'product' => [
                'display_name' => $displayName,
                'pos_qty'      => $product->pos_qty ?? 0,
                'pos_unit'     => '(' . strtolower($product->unit?->pos_unit ?? 'pcs') . ')',
            ],
            'history' => $result,
        ]);
    }
}
