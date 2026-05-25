<?php

namespace App\Http\Controllers;

use App\Models\product;
use App\Models\brand;
use App\Models\ProductUnit;
use App\Models\strength;
use App\Models\drugform;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if (request()->wantsJson()) {
            $search = $request->input('search');
            $query = product::with(['brand', 'unit'])->where('status', true);
            if (!empty($search)) {
                $query->where('productname', 'like', "{$search}%");
            }

            return response()->json([
                'products' => $query->orderBy('productname')->limit(10)->get(['id', 'productname', 'brand_id', 'product_unit_id', 'isgeneric'])->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'productname' => $product->productname,
                        'brand_name' => $product->brand?->brandname ?? 'N/A',
                        'unit_name' => $product->unit?->unit_name ?? 'N/A',
                        'isgeneric' => $product->isgeneric,
                    ];
                })
            ]);
        }


        $search = $request->input('search');
        $column = $request->input('column');
        $type = $request->input('type');


        $query = product::with(['brand', 'unit', 'drugform', 'productType'])->where('status', true);

        if ($type === 'generic') {
            $query->where('isgeneric', true);
        } elseif ($type === 'branded') {
            $query->where('isgeneric', false);
        }

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            if ($column === 'brand_name') {
                $query->whereHas('brand', function ($q) use ($search) {
                    $q->where('brandname', 'like', "{$search}%");
                });
            } elseif ($column === 'type_name') {
                $query->whereHas('productType', function ($q) use ($search) {
                    $q->where('type_name', 'like', "{$search}%");
                });
            } else {
                $query->where($column, 'like', "{$search}%");
            }
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($product) {
            $product->status_text = $product->status ? 'Active' : 'Inactive';
            $product->generic_text = $product->isgeneric ? 'Generic' : 'Branded';
            $product->brand_name = $product->brand?->brandname ?? 'N/A';
            $product->unit_name = $product->unit?->unit_name ?? 'N/A';
            $product->type_name = $product->productType?->type_name ?? 'N/A';

            // Build display name: productname drugform unit (brand)
            $parts = [$product->productname];
            if ($product->drugform) {
                $parts[] = $product->drugform->drugformname;
            }
            if ($product->unit) {
                $parts[] = $product->unit->unit_name;
            }
            $displayName = implode(' ', $parts);
            if ($product->brand) {
                $displayName .= ' (' . $product->brand->brandname . ')';
            }
            $product->display_name = $displayName;

            return $product;
        });

        $columns = [
            ['accessorKey' => 'id', 'header' => 'ID', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'generic_text', 'header' => 'TYPE', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'type_name', 'header' => 'CATEGORY', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'display_name', 'header' => 'PRODUCT NAME', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'productname', 'header' => 'PRODUCT NAME', 'isVisible' => false, 'isParameter' => true],
            ['accessorKey' => 'brand_name', 'header' => 'BRAND', 'isVisible' => false, 'isParameter' => true],
            ['accessorKey' => 'status_text', 'header' => 'STATUS', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'created_at', 'header' => 'CREATED AT', 'isVisible' => false, 'isParameter' => false],
        ];

        return inertia('Products/ProductIndex', [
            'products' => $products,
            'columns' => $columns,
            'brands' => brand::where('status', true)->orderBy('brandname')->get(['id', 'brandname']),
            'productUnits' => ProductUnit::where('status', true)->orderBy('unit_name')->get(['id', 'unit_name']),
            'strengths' => strength::where('status', true)->orderBy('strengthname')->get(['id', 'strengthname']),
            'drugforms' => drugform::where('status', true)->orderBy('drugformname')->get(['id', 'drugformname'])
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
            // Product information
            'productname' => 'required|string|max:255',
            'brand_id' => 'nullable|exists:brands,id',
            'product_unit_id' => 'required|exists:product_units,id',
            'product_type_id' => 'required|exists:product_types,id',
            'strength_id' => 'nullable|exists:strengths,id',
            'drugform_id' => 'nullable|exists:drugforms,id',
            'isgeneric' => 'boolean',
        ]);

        // Add system-generated fields
        $validated['created_by'] = $request->user()->id;

        product::create($validated);

        return redirect()->route('products.index')->with('success', 'Product created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, product $product)
    {
        $validated = $request->validate([
            // Product information
            'productname' => 'required|string|max:255',
            'brand_id' => 'nullable|exists:brands,id',
            'product_unit_id' => 'required|exists:product_units,id',
            'product_type_id' => 'required|exists:product_types,id',
            'strength_id' => 'nullable|exists:strengths,id',
            'drugform_id' => 'nullable|exists:drugforms,id',
            'isgeneric' => 'boolean',
        ]);

        // Add updated_by field
        $validated['updated_by'] = $request->user()->id;

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Product updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = product::findOrFail($id); // find product by ID or fail
        $product->update([
            'status' => false,
            'updated_by' => request()->user()->id
        ]); // soft delete by setting status to false

        return redirect()->route('products.index')->with('success', 'Product deactivated successfully!');
    }
}
