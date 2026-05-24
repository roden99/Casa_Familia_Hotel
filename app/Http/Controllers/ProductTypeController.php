<?php

namespace App\Http\Controllers;

use App\Models\ProductType;
use Illuminate\Http\Request;

class ProductTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (request()->wantsJson()) {
            $search = $request->input('search');

            $query = ProductType::where('status', 1);

            if (!empty($search)) {
                $query->where('type_name', 'like', "{$search}%");
            }
            return response()->json([
                'productTypes' => $query->orderBy('type_name')->limit(5)->get(['id', 'type_name', 'type_code'])
            ]);
        }

        $search = $request->input('search');
        $column = $request->input('column');

        $query = ProductType::query();

        $query->where('status', true);

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            $query->where($column, 'like', "{$search}%");
        }

        $productTypes = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($productType) {
            $productType->status_text = $productType->status ? 'Active' : 'Inactive';
            return $productType;
        });

        $columns = [
            ['accessorKey' => 'id', 'header' => 'ID', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'type_name', 'header' => 'TYPE NAME', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'type_code', 'header' => 'TYPE CODE', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'status_text', 'header' => 'STATUS', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'created_at', 'header' => 'CREATED AT', 'isVisible' => false, 'isParameter' => false],
        ];

        return inertia('ProductTypes/ProductTypeIndex', [
            'productTypes' => $productTypes,
            'columns' => $columns
        ]);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type_name' => 'required|string|max:255',
            'type_code' => 'nullable|string|max:50',
        ]);

        $validated['created_by'] = $request->user()->id;

        $productType = ProductType::create($validated);

        if ($request->expectsJson()) {
            return response()->json(['productType' => $productType]);
        }

        return redirect()->route('product-types.index')->with('success', 'Product Type created successfully!');
    }

    public function show(ProductType $productType)
    {
        //
    }

    public function edit(ProductType $productType)
    {
        //
    }

    public function update(Request $request, ProductType $productType)
    {
        $validated = $request->validate([
            'type_name' => 'required|string|max:255',
            'type_code' => 'nullable|string|max:50',
        ]);

        $validated['updated_by'] = $request->user()->id;

        $productType->update($validated);

        return redirect()->route('product-types.index')->with('success', 'Product Type updated successfully!');
    }

    public function destroy(Request $request, $id)
    {
        $productType = ProductType::findOrFail($id);

        $productType->update([
            'status' => false,
            'updated_by' => $request->user()->id
        ]);

        return redirect()->route('product-types.index')->with('success', 'Product Type deactivated successfully!');
    }
}
