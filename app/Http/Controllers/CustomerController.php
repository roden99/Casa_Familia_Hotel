<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');

        $query = Customer::query();

        // Show only active customers
        $query->where('status', 'active');

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            $query->where($column, 'like', "{$search}%");
        }

        $customers = $query->with('salesAccounts')->orderBy('created_at', 'desc')->paginate(15)->through(function ($customer) {
            return [
                'id'                  => $customer->id,
                'company'             => $customer->company,
                'first_name'          => $customer->first_name,
                'last_name'           => $customer->last_name,
                'middle_name'         => $customer->middle_name,
                'email'               => $customer->email,
                'phone'               => $customer->phone,
                'address'             => $customer->address,
                'status'              => $customer->status,
                'is_drugstore'        => $customer->is_drugstore ? 'Yes' : 'No',
                'full_name'           => trim(
                    strtoupper($customer->last_name) . ', ' .
                        strtoupper($customer->first_name) . ' ' .
                        strtoupper($customer->middle_name)
                ),
                'sales_account_id'   => $customer->salesAccounts->first()?->id,
                'sales_account_name' => $customer->salesAccounts->first()?->account_name ?? '',
            ];
        });

        $columns = [
            ['accessorKey' => 'id', 'header' => 'ID', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'is_drugstore', 'header' => 'DRUGSTORE', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'sales_account_name', 'header' => 'SALES ACCOUNT', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'company', 'header' => 'COMPANY', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'full_name', 'header' => 'CUSTOMER NAME', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'email', 'header' => 'EMAIL', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'phone', 'header' => 'PHONE', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'status', 'header' => 'STATUS', 'isVisible' => false, 'isParameter' => false],
        ];

        return inertia('Customers/CustomerIndex', [
            'customers' => $customers,
            'columns' => $columns
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
            'is_drugstore' => 'boolean',
            'company' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'sales_account_id' => 'nullable|exists:sales_accounts,id',
        ]);

        $salesAccountId = $validated['sales_account_id'] ? (int) $validated['sales_account_id'] : null;
        unset($validated['sales_account_id']);

        $validated['status'] = 'active';

        $customer = Customer::create($validated);
        $customer->salesAccounts()->sync($salesAccountId ? [$salesAccountId] : []);

        return redirect()->route('customers.index')->with('success', 'Customer created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'is_drugstore' => 'boolean',
            'company' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'sales_account_id' => 'nullable|exists:sales_accounts,id',
        ]);

        $salesAccountId = $validated['sales_account_id'] ? (int) $validated['sales_account_id'] : null;
        unset($validated['sales_account_id']);

        $customer->update($validated);
        $customer->salesAccounts()->sync($salesAccountId ? [$salesAccountId] : []);

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     * Soft delete by setting status to inactive.
     */
    public function destroy(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $customer->salesAccounts()->detach();

        // Soft delete: set status to inactive
        $customer->update([
            'status' => 'inactive'
        ]);

        return redirect()->route('customers.index')->with('success', 'Customer deactivated successfully!');
    }
}
