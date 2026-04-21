<?php

namespace App\Http\Controllers;

use App\Models\SalesAccount;
use Illuminate\Http\Request;

class SalesAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $salesAccounts = SalesAccount::when(request('search'), fn($q, $s) => $q->where('account_name', 'like', "%{$s}%"))
            ->where('status', 'active')
            ->get();

        return response()->json(['salesAccounts' => $salesAccounts]);
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(SalesAccount $salesAccount)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SalesAccount $salesAccount)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SalesAccount $salesAccount)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalesAccount $salesAccount)
    {
        //
    }
}
