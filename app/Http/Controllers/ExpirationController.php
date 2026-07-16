<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpirationController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');
        $filter = $request->input('filter');

        $today    = Carbon::today();
        $soonDate = Carbon::today()->addDays(90);

        $query = DB::table('product_lots as pl')
            ->join('products as p', 'p.id', '=', 'pl.product_id')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->leftJoin('drugforms as df', 'df.id', '=', 'p.drugform_id')
            ->leftJoin('product_units as pu', 'pu.id', '=', 'p.product_unit_id')
            ->where('p.status', true)
            ->select(
                'pl.id',
                'pl.lot_number',
                'pl.expiration_date',
                'pl.quantity',
                'p.id as product_id',
                'p.productname',
                'b.brandname',
                'df.drugformname',
                'pu.pos_unit'
            );

        if ($filter === 'expired') {
            $query->whereDate('pl.expiration_date', '<', $today);
        } elseif ($filter === 'soon') {
            $query->whereDate('pl.expiration_date', '>=', $today)
                ->whereDate('pl.expiration_date', '<=', $soonDate);
        } elseif ($filter === 'ok') {
            $query->whereDate('pl.expiration_date', '>', $soonDate);
        }

        if (!empty($search) && strlen($search) >= 2 && !empty($column)) {
            if ($column === 'product_name') {
                $query->where('p.productname', 'like', "%{$search}%");
            } elseif ($column === 'brand_name') {
                $query->where('b.brandname', 'like', "%{$search}%");
            } elseif ($column === 'lot_number') {
                $query->where('pl.lot_number', 'like', "%{$search}%");
            }
        }

        $lots = $query->orderBy('p.productname')->paginate(15)->through(function ($lot) use ($today) {
            $parts = [$lot->productname];
            if ($lot->drugformname) $parts[] = $lot->drugformname;
            if ($lot->pos_unit)     $parts[] = strtolower($lot->pos_unit) . ' (pcs)';
            $displayName = implode(' ', $parts);
            if ($lot->brandname)    $displayName .= ' (' . $lot->brandname . ')';

            $expiry          = Carbon::parse($lot->expiration_date);
            $daysUntilExpiry = (int) $today->diffInDays($expiry, false);

            $status = $daysUntilExpiry < 0
                ? 'Expired'
                : ($daysUntilExpiry <= 90 ? 'Expiring Soon' : 'Good');

            return [
                'id'                => $lot->id,
                'product_id'        => $lot->product_id,
                'product_name'      => $displayName,
                'brand_name'        => $lot->brandname ?? '—',
                'lot_number'        => $lot->lot_number,
                'expiration_date'   => $expiry->format('m-d-Y'),
                'days_until_expiry' => $daysUntilExpiry,
                'quantity'          => (float) $lot->quantity,
                'status'            => $status,
            ];
        });

        $columns = [
            ['accessorKey' => 'product_name',      'header' => 'PRODUCT',      'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'brand_name',        'header' => 'BRAND',        'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'lot_number',        'header' => 'LOT NO.',      'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'expiration_date',   'header' => 'EXPIRY DATE',  'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'days_until_expiry', 'header' => 'DAYS',         'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'quantity',          'header' => 'QTY',          'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'status',            'header' => 'STATUS',       'isVisible' => true,  'isParameter' => false],
        ];

        return inertia('Expirations/ExpirationIndex', [
            'lots'    => $lots,
            'columns' => $columns,
            'filter'  => $filter,
        ]);
    }
}
