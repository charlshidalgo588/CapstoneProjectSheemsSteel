<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryLogController extends Controller
{
    /**
     * Name of the unit column on the `products` table.
     * Change this if yours is called something else (e.g. 'UnitType', 'UOM').
     */
    private const UNIT_COLUMN = 'Unit';

    public function index(Request $request)
    {
        // "Top Adjusted Products" limit comes from the request (Show 10/25/50),
        // with a safe default + max cap.
        $topLimit = (int) $request->query('top_limit', 10);
        $topLimit = max(1, min($topLimit, 100)); // clamp between 1 and 100
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        $unitCol = 'products.' . self::UNIT_COLUMN;

        $applyDateFilter = function ($query) use ($dateFrom, $dateTo) {
            if ($dateFrom && $dateTo) {
                $query->whereBetween('inventory_logs.created_at', [
                    Carbon::parse($dateFrom)->startOfDay(),
                    Carbon::parse($dateTo)->endOfDay(),
                ]);
            }

            return $query;
        };

        // ✅ TRUE STOCK IN (from logs)
        $totalStockIn = $applyDateFilter(
            DB::table('inventory_logs')
                ->where('type', 'stock_in')
        )->sum('quantity');

        // ✅ TRUE STOCK OUT
        $totalStockOut = $applyDateFilter(
            DB::table('inventory_logs')
                ->where('type', 'stock_out')
        )->sum('quantity');

        // ✅ TOTAL ADJUSTMENTS — counts ALL log rows (POS sales + manual).
        $totalAdjustments = $applyDateFilter(
            DB::table('inventory_logs')
        )->count();

        // ✅ MANUAL ADJUSTMENTS — everything not tagged "POS sale #N".
        $manualAdjustments = $applyDateFilter(
            DB::table('inventory_logs')
                ->where('notes', 'not like', 'POS sale%')
        )->count();

        // ✅ TOTALS GROUPED BY UNIT (so kg and pcs are never added together)
        $totalsByUnit = function (string $type) use ($applyDateFilter, $unitCol) {
            return $applyDateFilter(
                DB::table('inventory_logs')
                    ->join('products', 'inventory_logs.ProductID', '=', 'products.ProductID')
                    ->where('inventory_logs.type', $type)
            )
                ->select(
                    DB::raw("COALESCE(NULLIF({$unitCol}, ''), 'pcs') as unit"),
                    DB::raw('SUM(inventory_logs.quantity) as total')
                )
                ->groupBy('unit')
                ->orderBy('unit')
                ->get();
        };

        // FULL LOGS (sorted newest first) — now includes the product's Unit
        $inventoryLogs = $applyDateFilter(
            DB::table('inventory_logs')
        )
            ->join('products', 'inventory_logs.ProductID', '=', 'products.ProductID')
            ->join('categories', 'products.CategoryID', '=', 'categories.CategoryID')
            ->select(
                'inventory_logs.*',
                'products.ProductName',
                'products.SKU',
                DB::raw("{$unitCol} as Unit"),
                'categories.CategoryName'
            )
            ->orderBy('inventory_logs.created_at', 'desc')
            ->get();

        $summary = [
            'total_adjustments'  => $totalAdjustments,
            'total_stock_in'     => $totalStockIn,
            'total_stock_out'    => $totalStockOut,
            'manual_adjustments' => $manualAdjustments,
            'stock_in_by_unit'   => $totalsByUnit('stock_in'),
            'stock_out_by_unit'  => $totalsByUnit('stock_out'),
        ];

        return response()->json([
            'summary' => $summary,

            // Same full, newest-first dataset as Full History; the table's own
            // pagination handles "recent" paging on the frontend.
            'recent_activity' => $inventoryLogs->values(),

            'top_adjusted_products' => $applyDateFilter(
                DB::table('inventory_logs')
            )
                ->join('products', 'inventory_logs.ProductID', '=', 'products.ProductID')
                ->select(
                    'products.ProductID',
                    'products.ProductName',
                    'products.SKU',
                    DB::raw("{$unitCol} as Unit"),
                    DB::raw('COUNT(*) as adjustment_count'),
                    DB::raw('SUM(CASE WHEN inventory_logs.type = "stock_in" THEN inventory_logs.quantity ELSE 0 END) as total_stock_in'),
                    DB::raw('SUM(CASE WHEN inventory_logs.type = "stock_out" THEN inventory_logs.quantity ELSE 0 END) as total_stock_out')
                )
                ->groupBy('products.ProductID', 'products.ProductName', 'products.SKU', $unitCol)
                ->orderBy('adjustment_count', 'desc')
                ->limit($topLimit)
                ->get(),

            'inventory_logs' => $inventoryLogs,
        ], 200);
    }
}