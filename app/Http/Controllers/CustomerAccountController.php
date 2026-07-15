<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesAccount;
use App\Models\CustomerSalesAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerAccountController extends Controller
{
    /**
     * Display a listing of customers with their corresponding sales account.
     */
    public function index(Request $request)
    {
        // JSON branch: used by SalesOrderForm combobox
        if ($request->expectsJson()) {
            $search    = $request->input('search', '');
            $includeId = $request->input('include_id');

            $query = DB::table('customer_sales_account as csa')
                ->join('customers as c', 'c.id', '=', 'csa.customer_id')
                ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
                ->where('c.status', 'active')
                ->select('csa.id', 'c.company', 'c.first_name', 'c.last_name', 'c.is_drugstore', 'sa.account_name', 'csa.discount_percentage');

            if ($includeId) {
                $query->where(function ($q) use ($search) {
                    if (!empty($search)) {
                        $q->where('c.last_name', 'like', "%{$search}%")
                            ->orWhere('c.company', 'like', "%{$search}%");
                    }
                })->orWhere('csa.id', $includeId);
            } elseif (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('c.last_name', 'like', "%{$search}%")
                        ->orWhere('c.company', 'like', "%{$search}%");
                });
            }

            $accounts = $query->orderBy('sa.account_name')->orderBy('c.last_name')->limit(20)->get()
                ->map(fn($row) => [
                    'value'               => (string) $row->id,
                    'label'               => strtoupper($row->account_name) . ' - ' . (
                        $row->is_drugstore
                        ? strtoupper($row->company)
                        : trim(strtoupper($row->last_name) . ', ' . strtoupper($row->first_name))
                    ),
                    'discount_percentage' => (float) $row->discount_percentage,
                ]);

            return response()->json(['accounts' => $accounts]);
        }

        $search    = $request->input('search');
        $column    = $request->input('column');
        $accountId = $request->input('account');
        $type      = $request->input('type');

        $query = DB::table('customer_sales_account as csa')
            ->join('customers as c', 'c.id', '=', 'csa.customer_id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->where('c.status', 'active')
            ->select(
                'csa.id as csa_id',
                'c.id',
                'c.company',
                'c.last_name',
                'c.first_name',
                'c.is_drugstore',
                'c.phone',
                'c.address',
                'sa.account_name',
                DB::raw('IFNULL(csa.forward_balance, 0)
                    + IFNULL((
                        SELECT SUM(soi.quantity * soi.unit_price * (1 - IFNULL(soi.discount_percentage,0)/100))
                        FROM sales_orders so
                        JOIN sales_order_items soi ON soi.sales_order_id = so.id
                        WHERE so.customer_sales_account_id = csa.id
                    ), 0)
                    + IFNULL((
                        SELECT SUM(i.amount)
                        FROM customer_account_invoices i
                        WHERE i.customer_sales_account_id = csa.id
                    ), 0)
                    - IFNULL((
                        SELECT SUM(p.amount)
                        FROM customer_sales_account_payments p
                        WHERE p.customer_sales_account_id = csa.id
                    ), 0) AS balance')
            );

        if (!empty($accountId) && is_numeric($accountId)) {
            $query->where('sa.id', (int) $accountId);
        }

        if ($type === 'drugstore') {
            $query->where('c.is_drugstore', true);
        } elseif ($type === 'person') {
            $query->where('c.is_drugstore', false);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('c.company', 'like', "%{$search}%")
                    ->orWhere('c.last_name', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('sa.account_name')->orderBy('c.last_name')->paginate(15)->through(function ($row) {
            return [
                'id'           => $row->id,
                'csa_id'       => $row->csa_id,
                'display_name' => $row->is_drugstore
                    ? strtoupper($row->company)
                    : trim(strtoupper($row->last_name) . ', ' . strtoupper($row->first_name)),
                'company'      => strtoupper($row->company),
                'last_name'    => strtoupper($row->last_name),
                'phone'        => $row->phone,
                'address'      => strtoupper($row->address),
                'account_name' => strtoupper($row->account_name),
                'is_drugstore' => $row->is_drugstore ? 'YES' : 'NO',
                'balance'      => number_format((float) $row->balance, 2),
            ];
        });

        $columns = [
            ['accessorKey' => 'id',           'header' => 'ID',             'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'account_name',  'header' => 'ACCOUNT',        'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'is_drugstore',  'header' => 'LEGEND',         'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'display_name',  'header' => 'CUSTOMER NAME',  'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'company',       'header' => 'COMPANY',        'isVisible' => false, 'isParameter' => true],
            ['accessorKey' => 'last_name',     'header' => 'LAST NAME',      'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'phone',         'header' => 'PHONE',          'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'address',       'header' => 'ADDRESS',        'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'balance',       'header' => 'BALANCE',        'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'status',        'header' => 'STATUS',         'isVisible' => false, 'isParameter' => false],
        ];

        $accounts = SalesAccount::where('status', 'active')
            ->orderBy('account_name')
            ->get(['id', 'account_name'])
            ->map(fn($a) => ['value' => (string) $a->id, 'label' => strtoupper($a->account_name)]);

        return inertia('CustomerAccount/CustsomerAccountIndex', [
            'customers' => $customers,
            'columns'   => $columns,
            'accounts'  => $accounts,
        ]);
    }

    /**
     * Attach a customer to a sales account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_ids'     => 'required|array|min:1',
            'customer_ids.*'   => 'required|integer|exists:customers,id',
            'sales_account_id' => 'required|exists:sales_accounts,id',
        ]);

        $results = [];
        foreach ($validated['customer_ids'] as $customerId) {
            $customer = Customer::findOrFail($customerId);
            if ($customer->salesAccounts()->where('sales_accounts.id', $validated['sales_account_id'])->exists()) {
                $results[] = [
                    'customer_id' => $customerId,
                    'name'        => $customer->display_name,
                    'success'     => false,
                    'message'     => $customer->display_name . ' is already assigned to this account.',
                ];
            } else {
                $customer->salesAccounts()->attach($validated['sales_account_id']);
                $results[] = [
                    'customer_id' => $customerId,
                    'name'        => $customer->display_name,
                    'success'     => true,
                ];
            }
        }

        $successCount = collect($results)->where('success', true)->count();

        return response()->json([
            'message' => "{$successCount} customer(s) assigned successfully.",
            'results' => $results,
        ]);
    }

    /**
     * Return unpaid sales orders AND manual invoices for a customer sales account.
     */
    public function unpaidOrders(int $id)
    {
        // Unpaid sales orders
        $orders = DB::table('sales_orders as so')
            ->where('so.customer_sales_account_id', $id)
            ->whereNull('so.payment_id')
            ->select('so.id', 'so.invoice_no', 'so.invoice_date', 'so.terms')
            ->orderBy('so.invoice_date')
            ->get()
            ->map(function ($row) {
                $total = DB::table('sales_order_items')
                    ->where('sales_order_id', $row->id)
                    ->sum(DB::raw('quantity * unit_price * (1 - IFNULL(discount_percentage, 0) / 100)'));
                $dueDate = ($row->invoice_date && $row->terms)
                    ? \Carbon\Carbon::parse($row->invoice_date)->addDays((int) $row->terms)->format('m-d-Y')
                    : null;
                return [
                    'id'           => $row->id,
                    'type'         => 'order',
                    'invoice_no'   => $row->invoice_no ?? '—',
                    'invoice_date' => $row->invoice_date ? \Carbon\Carbon::parse($row->invoice_date)->format('m-d-Y') : null,
                    'due_date'     => $dueDate,
                    'total'        => round((float) $total, 2),
                ];
            });

        // Unpaid / partially-paid manual invoices
        $invoices = DB::table('customer_account_invoices as i')
            ->where('i.customer_sales_account_id', $id)
            ->leftJoinSub(
                DB::table('customer_account_invoice_payments')
                    ->select('customer_account_invoice_id', DB::raw('SUM(amount) as paid_amount'))
                    ->groupBy('customer_account_invoice_id'),
                'pmts',
                'pmts.customer_account_invoice_id',
                '=',
                'i.id'
            )
            ->whereRaw('IFNULL(pmts.paid_amount, 0) < i.amount')
            ->select('i.id', 'i.reference_no', 'i.invoice_date', 'i.terms', 'i.amount')
            ->orderBy('i.invoice_date')
            ->get()
            ->map(fn($row) => [
                'id'           => $row->id,
                'type'         => 'invoice',
                'invoice_no'   => $row->reference_no ?? '—',
                'invoice_date' => $row->invoice_date ? \Carbon\Carbon::parse($row->invoice_date)->format('m-d-Y') : null,
                'due_date'     => ($row->invoice_date && $row->terms)
                    ? \Carbon\Carbon::parse($row->invoice_date)->addDays((int) $row->terms)->format('m-d-Y')
                    : null,
                'total'        => round((float) $row->amount, 2),
            ]);

        $all = $orders->concat($invoices)->sortBy('invoice_date')->values();

        return response()->json(['orders' => $all]);
    }

    /**
     * Return orders + invoices available for a specific payment edit (pre-selected if linked).
     */
    public function ordersForPayment(int $id, int $paymentId)
    {
        $orders = DB::table('sales_orders as so')
            ->where('so.customer_sales_account_id', $id)
            ->where(function ($q) use ($paymentId) {
                $q->whereNull('so.payment_id')->orWhere('so.payment_id', $paymentId);
            })
            ->select('so.id', 'so.invoice_no', 'so.invoice_date', 'so.terms', 'so.payment_id')
            ->orderBy('so.invoice_date')
            ->get()
            ->map(function ($row) use ($paymentId) {
                $total = DB::table('sales_order_items')
                    ->where('sales_order_id', $row->id)
                    ->sum(DB::raw('quantity * unit_price * (1 - IFNULL(discount_percentage, 0) / 100)'));
                $dueDate = ($row->invoice_date && $row->terms)
                    ? \Carbon\Carbon::parse($row->invoice_date)->addDays((int) $row->terms)->format('m-d-Y')
                    : null;
                return [
                    'id'           => $row->id,
                    'type'         => 'order',
                    'invoice_no'   => $row->invoice_no ?? '—',
                    'invoice_date' => $row->invoice_date ? \Carbon\Carbon::parse($row->invoice_date)->format('m-d-Y') : null,
                    'due_date'     => $dueDate,
                    'total'        => round((float) $total, 2),
                    'selected'     => (int) $row->payment_id === $paymentId,
                ];
            });

        $invoices = DB::table('customer_account_invoices as i')
            ->where('i.customer_sales_account_id', $id)
            ->leftJoinSub(
                DB::table('customer_account_invoice_payments')
                    ->select('customer_account_invoice_id', DB::raw('SUM(amount) as paid_amount'))
                    ->groupBy('customer_account_invoice_id'),
                'pmts',
                'pmts.customer_account_invoice_id', '=', 'i.id'
            )
            ->select('i.id', 'i.reference_no', 'i.invoice_date', 'i.terms', 'i.amount', 'pmts.paid_amount')
            ->orderBy('i.invoice_date')
            ->get()
            ->map(fn($row) => [
                'id'           => $row->id,
                'type'         => 'invoice',
                'invoice_no'   => $row->reference_no ?? '—',
                'invoice_date' => $row->invoice_date ? \Carbon\Carbon::parse($row->invoice_date)->format('m-d-Y') : null,
                'due_date'     => ($row->invoice_date && $row->terms)
                    ? \Carbon\Carbon::parse($row->invoice_date)->addDays((int) $row->terms)->format('m-d-Y')
                    : null,
                'total'        => round((float) $row->amount, 2),
                'selected'     => $row->paid_amount !== null && (float) $row->paid_amount >= (float) $row->amount,
            ]);

        $all = $orders->concat($invoices)->sortBy('invoice_date')->values();

        return response()->json(['orders' => $all]);
    }

    /**
     * Record a payment for a customer sales account.
     */
    public function storePayment(Request $request, int $id)
    {
        $isCheque = $request->input('payment_method') === 'Cheque';

        $validated = $request->validate([
            'amount'          => 'required|numeric|min:0.01',
            'payment_date'    => 'required|date',
            'reference_no'    => 'nullable|string|max:255',
            'payment_method'  => 'nullable|string|max:100',
            'check_number'    => ($isCheque ? 'required' : 'nullable') . '|string|max:100',
            'check_date'      => ($isCheque ? 'required' : 'nullable') . '|date',
            'notes'           => 'nullable|string',
            'sales_order_ids'   => 'nullable|array',
            'sales_order_ids.*' => 'integer|exists:sales_orders,id',
            'invoice_ids'       => 'nullable|array',
            'invoice_ids.*'     => 'integer|exists:customer_account_invoices,id',
        ]);

        if (!empty($validated['sales_order_ids']) && !empty($validated['invoice_ids'])) {
            return back()->withErrors(['mixed' => 'A payment cannot cover both sales orders and invoices at the same time.']);
        }

        $paymentId = DB::table('customer_sales_account_payments')->insertGetId([
            'customer_sales_account_id' => $id,
            'amount'                    => $validated['amount'],
            'payment_date'              => $validated['payment_date'],
            'reference_no'              => $validated['reference_no'] ?? null,
            'payment_method'            => $validated['payment_method'] ?? null,
            'check_number'              => $validated['check_number'] ?? null,
            'check_date'                => $validated['check_date'] ?? null,
            'notes'                     => $validated['notes'] ?? null,
            'created_by'                => $request->user()->id,
            'updated_by'                => $request->user()->id,
            'created_at'                => now(),
            'updated_at'                => now(),
        ]);

        if (!empty($validated['sales_order_ids'])) {
            DB::table('sales_orders')
                ->whereIn('id', $validated['sales_order_ids'])
                ->where('customer_sales_account_id', $id)
                ->update(['payment_id' => $paymentId, 'updated_at' => now()]);
        }

        if (!empty($validated['invoice_ids'])) {
            $invoices = DB::table('customer_account_invoices')
                ->whereIn('id', $validated['invoice_ids'])
                ->where('customer_sales_account_id', $id)
                ->select('id', 'amount')
                ->get();

            $now = now();
            foreach ($invoices as $invoice) {
                DB::table('customer_account_invoice_payments')->insert([
                    'customer_account_invoice_id' => $invoice->id,
                    'amount'                      => $invoice->amount,
                    'payment_date'                => $validated['payment_date'],
                    'reference_no'                => $validated['reference_no'] ?? null,
                    'payment_method'              => $validated['payment_method'] ?? null,
                    'check_number'                => $validated['check_number'] ?? null,
                    'check_date'                  => $validated['check_date'] ?? null,
                    'notes'                       => $validated['notes'] ?? null,
                    'created_by'                  => $request->user()->id,
                    'updated_by'                  => $request->user()->id,
                    'created_at'                  => $now,
                    'updated_at'                  => $now,
                ]);
            }
        }

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Payment recorded successfully!');
    }

    /**
     * Set the forward (opening) balance for a customer sales account.
     */
    public function setForwardBalance(Request $request, int $id)
    {
        $validated = $request->validate([
            'forward_balance'      => 'required|numeric|min:0',
            'forward_balance_date' => 'required|date',
        ]);

        DB::table('customer_sales_account')
            ->where('id', $id)
            ->update([
                'forward_balance'      => $validated['forward_balance'],
                'forward_balance_date' => $validated['forward_balance_date'],
                'updated_at'           => now(),
            ]);

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Forward balance set successfully!');
    }

    /**
     * Return ledger entries for a customer sales account.
     */
    public function ledger(int $id)
    {
        $csa = DB::table('customer_sales_account as csa')
            ->join('customers as c', 'c.id', '=', 'csa.customer_id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->where('csa.id', $id)
            ->select(
                'csa.id',
                'sa.account_name',
                'c.company',
                'c.last_name',
                'c.first_name',
                'c.is_drugstore',
                'csa.forward_balance',
                'csa.forward_balance_date'
            )
            ->first();

        if (!$csa) {
            return response()->json(['error' => 'Account not found.'], 404);
        }

        // ── INVOICES from sales orders ────────────────────────────────────
        $invoices = DB::table('sales_orders as so')
            ->join('sales_order_items as soi', 'soi.sales_order_id', '=', 'so.id')
            ->where('so.customer_sales_account_id', $id)
            ->select(
                'so.id as order_id',
                'so.invoice_no',
                'so.invoice_date as date',
                DB::raw('SUM(soi.quantity * soi.unit_price * (1 - IFNULL(soi.discount_percentage,0)/100)) as amount')
            )
            ->groupBy('so.id', 'so.invoice_no', 'so.invoice_date')
            ->get()
            ->map(fn($row) => [
                'type'       => 'INVOICE',
                'reference'  => 'SO #' . $row->order_id,
                'invoice_no' => $row->invoice_no ?? '—',
                'amount'     => (float) $row->amount,
                'date'       => $row->date ? \Carbon\Carbon::parse($row->date) : null,
            ]);

        // ── MANUAL INVOICES ───────────────────────────────────────────────
        $manualInvoices = DB::table('customer_account_invoices')
            ->where('customer_sales_account_id', $id)
            ->select('id', 'reference_no', 'invoice_date as date', 'amount', 'terms', 'notes')
            ->get()
            ->map(fn($row) => [
                'type'       => 'INVOICE',
                'is_manual'  => true,
                'invoice_id' => $row->id,
                'reference'  => 'INV #' . $row->id,
                'invoice_no' => $row->reference_no ?? '—',
                'amount'     => (float) $row->amount,
                'raw_amount' => (float) $row->amount,
                'raw_date'   => $row->date,
                'terms'      => $row->terms !== null ? (int) $row->terms : null,
                'notes'      => $row->notes ?? '',
                'date'       => $row->date ? \Carbon\Carbon::parse($row->date) : null,
            ]);

        // ── PAYMENTS ──────────────────────────────────────────────────────
        $payments = DB::table('customer_sales_account_payments')
            ->where('customer_sales_account_id', $id)
            ->select('id', 'amount', 'payment_date as date', 'reference_no', 'payment_method', 'check_date', 'check_number', 'notes')
            ->get()
            ->map(fn($row) => [
                'type'           => 'PAYMENT',
                'is_payment'     => true,
                'payment_id'     => $row->id,
                'reference'      => 'PMT #' . $row->id,
                'invoice_no'     => $row->reference_no ?? '—',
                'amount'         => (float) $row->amount,
                'raw_amount'     => (float) $row->amount,
                'raw_date'       => $row->date,
                'payment_method' => $row->payment_method ?? 'Cash',
                'check_date'     => $row->check_date ?? null,
                'check_number'   => $row->check_number ?? null,
                'notes'          => $row->notes ?? '',
                'date'           => $row->date ? \Carbon\Carbon::parse($row->date) : null,
            ]);

        // ── Merge & sort by date ──────────────────────────────────────────
        $entries = $invoices->concat($manualInvoices)->concat($payments)
            ->sortBy(fn($e) => $e['date'] ?? \Carbon\Carbon::minValue())
            ->values();

        // ── Running balance (debit = invoice, credit = payment) ───────────
        $balance = (float) ($csa->forward_balance ?? 0);

        // Prepend FORWARD entry if set
        $forwardEntry = null;
        if ($balance > 0 && $csa->forward_balance_date) {
            $forwardEntry = [
                'type'       => 'FORWARD',
                'reference'  => 'Forward Balance',
                'invoice_no' => '—',
                'amount'     => number_format($balance, 2),
                'balance'    => number_format($balance, 2),
                'date'       => \Carbon\Carbon::parse($csa->forward_balance_date)->format('m-d-Y'),
            ];
        }

        $ledger = $entries->map(function ($entry) use (&$balance) {
            if ($entry['type'] === 'INVOICE') {
                $balance += $entry['amount'];
            } else {
                $balance -= $entry['amount'];
            }
            return array_merge($entry, [
                'balance' => number_format($balance, 2),
                'amount'  => number_format($entry['amount'], 2),
                'date'    => $entry['date'] ? $entry['date']->format('m-d-Y') : '—',
            ]);
        });

        if ($forwardEntry) {
            $ledger = collect([$forwardEntry])->concat($ledger);
        }

        $customerName = $csa->is_drugstore
            ? strtoupper($csa->company)
            : trim(strtoupper($csa->last_name) . ', ' . strtoupper($csa->first_name));

        return response()->json([
            'account' => [
                'id'           => $csa->id,
                'account_name' => strtoupper($csa->account_name),
                'customer'     => $customerName,
                'balance'      => number_format($balance, 2),
                'forward_balance' => number_format((float) ($csa->forward_balance ?? 0), 2),
            ],
            'ledger' => $ledger,
        ]);
    }

    /**
     * Store a manual (previous) invoice for a customer sales account.
     */
    public function storeInvoice(Request $request, int $id)
    {
        $validated = $request->validate([
            'reference_no' => 'nullable|string|max:255',
            'invoice_date' => 'required|date',
            'amount'       => 'required|numeric|min:0.01',
            'terms'        => 'nullable|integer|min:0',
            'notes'        => 'nullable|string',
        ]);

        DB::table('customer_account_invoices')->insert([
            'customer_sales_account_id' => $id,
            'reference_no'              => $validated['reference_no'] ?? null,
            'invoice_date'              => $validated['invoice_date'],
            'amount'                    => $validated['amount'],
            'terms'                     => $validated['terms'] ?? null,
            'notes'                     => $validated['notes'] ?? null,
            'created_by'                => $request->user()->id,
            'updated_by'                => $request->user()->id,
            'created_at'                => now(),
            'updated_at'                => now(),
        ]);

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Invoice recorded successfully!');
    }

    /**
     * Update a manual invoice for a customer sales account.
     */
    public function updateInvoice(Request $request, int $csaId, int $invoiceId)
    {
        $validated = $request->validate([
            'reference_no' => 'nullable|string|max:255',
            'invoice_date' => 'required|date',
            'amount'       => 'required|numeric|min:0.01',
            'terms'        => 'nullable|integer|min:0',
            'notes'        => 'nullable|string',
        ]);

        DB::table('customer_account_invoices')
            ->where('id', $invoiceId)
            ->where('customer_sales_account_id', $csaId)
            ->update([
                'reference_no' => $validated['reference_no'] ?? null,
                'invoice_date' => $validated['invoice_date'],
                'amount'       => $validated['amount'],
                'terms'        => $validated['terms'] ?? null,
                'notes'        => $validated['notes'] ?? null,
                'updated_by'   => $request->user()->id,
                'updated_at'   => now(),
            ]);

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Invoice updated successfully!');
    }

    /**
     * Update a payment for a customer sales account.
     */
    public function updatePayment(Request $request, int $csaId, int $paymentId)
    {
        $isCheque = $request->input('payment_method') === 'Cheque';

        $validated = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'reference_no'   => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:100',
            'check_number'   => ($isCheque ? 'required' : 'nullable') . '|string|max:100',
            'check_date'     => ($isCheque ? 'required' : 'nullable') . '|date',
            'notes'          => 'nullable|string',
            'sales_order_ids'   => 'nullable|array',
            'sales_order_ids.*' => 'integer|exists:sales_orders,id',
            'invoice_ids'       => 'nullable|array',
            'invoice_ids.*'     => 'integer|exists:customer_account_invoices,id',
        ]);

        if (!empty($validated['sales_order_ids']) && !empty($validated['invoice_ids'])) {
            return back()->withErrors(['mixed' => 'A payment cannot cover both sales orders and invoices at the same time.']);
        }

        DB::table('customer_sales_account_payments')
            ->where('id', $paymentId)
            ->where('customer_sales_account_id', $csaId)
            ->update([
                'amount'         => $validated['amount'],
                'payment_date'   => $validated['payment_date'],
                'reference_no'   => $validated['reference_no'] ?? null,
                'payment_method' => $validated['payment_method'] ?? null,
                'check_number'   => $validated['check_number'] ?? null,
                'check_date'     => $validated['check_date'] ?? null,
                'notes'          => $validated['notes'] ?? null,
                'updated_by'     => $request->user()->id,
                'updated_at'     => now(),
            ]);

        // Unlink previously linked sales orders
        DB::table('sales_orders')
            ->where('payment_id', $paymentId)
            ->where('customer_sales_account_id', $csaId)
            ->update(['payment_id' => null, 'updated_at' => now()]);

        // Re-link selected sales orders
        if (!empty($validated['sales_order_ids'])) {
            DB::table('sales_orders')
                ->whereIn('id', $validated['sales_order_ids'])
                ->where('customer_sales_account_id', $csaId)
                ->update(['payment_id' => $paymentId, 'updated_at' => now()]);
        }

        // Sync invoice payments: find previously paid invoices for this CSA
        $prevPaidIds = DB::table('customer_account_invoice_payments as caip')
            ->join('customer_account_invoices as i', 'i.id', '=', 'caip.customer_account_invoice_id')
            ->where('i.customer_sales_account_id', $csaId)
            ->pluck('caip.customer_account_invoice_id')
            ->unique()
            ->values()
            ->toArray();

        $newInvoiceIds = $validated['invoice_ids'] ?? [];

        // Delete payment records for invoices removed from this payment
        $toRemove = array_values(array_diff($prevPaidIds, $newInvoiceIds));
        if (!empty($toRemove)) {
            DB::table('customer_account_invoice_payments')
                ->whereIn('customer_account_invoice_id', $toRemove)
                ->delete();
        }

        // Insert payment records for newly added invoices
        $toAdd = array_values(array_diff($newInvoiceIds, $prevPaidIds));
        if (!empty($toAdd)) {
            $newInvoices = DB::table('customer_account_invoices')
                ->whereIn('id', $toAdd)
                ->where('customer_sales_account_id', $csaId)
                ->select('id', 'amount')
                ->get();

            $now = now();
            foreach ($newInvoices as $invoice) {
                DB::table('customer_account_invoice_payments')->insert([
                    'customer_account_invoice_id' => $invoice->id,
                    'amount'                      => $invoice->amount,
                    'payment_date'                => $validated['payment_date'],
                    'reference_no'                => $validated['reference_no'] ?? null,
                    'payment_method'              => $validated['payment_method'] ?? null,
                    'check_number'                => $validated['check_number'] ?? null,
                    'check_date'                  => $validated['check_date'] ?? null,
                    'notes'                       => $validated['notes'] ?? null,
                    'created_by'                  => $request->user()->id,
                    'updated_by'                  => $request->user()->id,
                    'created_at'                  => $now,
                    'updated_at'                  => $now,
                ]);
            }
        }

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Payment updated successfully!');
    }

    /**
     * Record a payment for a specific customer account invoice.
     */
    public function storeInvoicePayment(Request $request, int $invoiceId)
    {
        $isCheque = $request->input('payment_method') === 'Cheque';

        $validated = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'reference_no'   => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:100',
            'check_number'   => ($isCheque ? 'required' : 'nullable') . '|string|max:100',
            'check_date'     => ($isCheque ? 'required' : 'nullable') . '|date',
            'notes'          => 'nullable|string',
        ]);

        DB::table('customer_account_invoice_payments')->insert([
            'customer_account_invoice_id' => $invoiceId,
            'amount'                      => $validated['amount'],
            'payment_date'                => $validated['payment_date'],
            'reference_no'                => $validated['reference_no'] ?? null,
            'payment_method'              => $validated['payment_method'] ?? null,
            'check_number'                => $validated['check_number'] ?? null,
            'check_date'                  => $validated['check_date'] ?? null,
            'notes'                       => $validated['notes'] ?? null,
            'created_by'                  => $request->user()->id,
            'updated_by'                  => $request->user()->id,
            'created_at'                  => now(),
            'updated_at'                  => now(),
        ]);

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Invoice payment recorded successfully!');
    }

    /**
     * Update a payment for a specific customer account invoice.
     */
    public function updateInvoicePayment(Request $request, int $invoiceId, int $paymentId)
    {
        $isCheque = $request->input('payment_method') === 'Cheque';

        $validated = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'reference_no'   => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:100',
            'check_number'   => ($isCheque ? 'required' : 'nullable') . '|string|max:100',
            'check_date'     => ($isCheque ? 'required' : 'nullable') . '|date',
            'notes'          => 'nullable|string',
        ]);

        DB::table('customer_account_invoice_payments')
            ->where('id', $paymentId)
            ->where('customer_account_invoice_id', $invoiceId)
            ->update([
                'amount'         => $validated['amount'],
                'payment_date'   => $validated['payment_date'],
                'reference_no'   => $validated['reference_no'] ?? null,
                'payment_method' => $validated['payment_method'] ?? null,
                'check_number'   => $validated['check_number'] ?? null,
                'check_date'     => $validated['check_date'] ?? null,
                'notes'          => $validated['notes'] ?? null,
                'updated_by'     => $request->user()->id,
                'updated_at'     => now(),
            ]);

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Invoice payment updated successfully!');
    }

    /**
     * Delete a payment for a specific customer account invoice.
     */
    public function destroyInvoicePayment(int $invoiceId, int $paymentId)
    {
        DB::table('customer_account_invoice_payments')
            ->where('id', $paymentId)
            ->where('customer_account_invoice_id', $invoiceId)
            ->delete();

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Invoice payment deleted successfully!');
    }

    /**
     * Delete a manual invoice.
     */
    public function destroyInvoice(int $csaId, int $invoiceId)
    {
        DB::table('customer_account_invoices')
            ->where('id', $invoiceId)
            ->where('customer_sales_account_id', $csaId)
            ->delete();

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Invoice deleted successfully!');
    }

    /**
     * Delete a payment.
     */
    public function destroyPayment(int $csaId, int $paymentId)
    {
        DB::table('customer_sales_account_payments')
            ->where('id', $paymentId)
            ->where('customer_sales_account_id', $csaId)
            ->delete();

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Payment deleted successfully!');
    }
}
