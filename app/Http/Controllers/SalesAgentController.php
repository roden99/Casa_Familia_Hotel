<?php

namespace App\Http\Controllers;

use App\Models\SalesAgent;
use Illuminate\Http\Request;

class SalesAgentController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $search = $request->input('search');
            $includeId = $request->input('include_id');

            $query = SalesAgent::where('status', 'active');

            if (!empty($search)) {
                $query->where('name', 'like', "{$search}%");
            }

            $results = $query->orderBy('name')->limit(5)->get(['id', 'name']);

            if ($includeId && !$results->contains('id', (int)$includeId)) {
                $extra = SalesAgent::where('id', (int)$includeId)->first(['id', 'name']);
                if ($extra) $results->prepend($extra);
            }

            return response()->json(['sales_agents' => $results]);
        }

        $search = $request->input('search');
        $column = $request->input('column');

        $query = SalesAgent::query()->where('status', 'active');

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            $query->where($column, 'like', "{$search}%");
        }

        $salesAgents = $query->orderBy('name')->paginate(15);

        $columns = [
            ['accessorKey' => 'id',    'header' => 'ID',    'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'name',  'header' => 'NAME',  'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'email', 'header' => 'EMAIL', 'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'phone', 'header' => 'PHONE', 'isVisible' => true,  'isParameter' => false],
        ];

        return inertia('SalesAgents/SalesAgentIndex', [
            'salesAgents' => $salesAgents,
            'columns'     => $columns,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $validated['status']     = 'active';
        $validated['created_by'] = $request->user()->id;

        $salesAgent = SalesAgent::create($validated);

        if ($request->expectsJson()) {
            return response()->json(['sales_agent' => $salesAgent]);
        }
    }

    public function update(Request $request, SalesAgent $salesAgent)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $validated['updated_by'] = $request->user()->id;

        $salesAgent->update($validated);
    }

    public function destroy(Request $request, $id)
    {
        $salesAgent = SalesAgent::findOrFail($id);

        $salesAgent->update([
            'status'     => 'inactive',
            'updated_by' => $request->user()->id,
        ]);
    }
}
