<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryLogController extends Controller
{
    public function index(Request $request)
    {
        // FIX: "Top Adjusted Products" limit used to be hardcoded to 5,
        // so the frontend's "Show 10/25/50" dropdown had no effect — it
        // was paginating a 5-row array client-side, which can never show
        // more than 5. Now the limit comes from the request, controlled
        // by whatever the user picks, with a safe default + max cap.
        $topLimit = (int) $request->query('top_limit', 10);
        $topLimit = max(1, min($topLimit, 100)); // clamp between 1 and 100
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

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

        // ✅ TOTAL ADJUSTMENTS
        // FIX: this used to be wired to the same value as Manual Adjustments,
        // so it only ever showed the manual-only count (2) instead of every
        // inventory movement. "Total Adjustments" should count ALL log rows
        // (POS sales + manual actions combined) — this matches the sum of
        // the "Adjustments" column across every product in Top Adjusted
        // Products, which is what the user sees and expects this to equal.
$totalAdjustments = $applyDateFilter(
    DB::table('inventory_logs')
)->count();
        // ✅ MANUAL ADJUSTMENTS
        // There is no type = 'adjustment' value in this table — every row
        // is either 'stock_in' or 'stock_out'. Manual actions (restocks,
        // rollbacks, corrections) are only distinguishable by their notes
        // text ("Manual restock", "Rollback of sale #9"), while POS-driven
        // rows are always tagged "POS sale #N".
        $manualAdjustments = $applyDateFilter(
    DB::table('inventory_logs')
        ->where('notes', 'not like', 'POS sale%')
)->count();

        // FULL LOGS (sorted newest first)
$inventoryLogs = $applyDateFilter(
    DB::table('inventory_logs')
)            ->join('products', 'inventory_logs.ProductID', '=', 'products.ProductID')
            ->join('categories', 'products.CategoryID', '=', 'categories.CategoryID')
            ->select(
                'inventory_logs.*',
                'products.ProductName',
                'products.SKU',
                'categories.CategoryName'
            )
            ->orderBy('inventory_logs.created_at', 'desc')
            ->get();

        $summary = [
            'total_adjustments'  => $totalAdjustments,
            'total_stock_in'     => $totalStockIn,
            'total_stock_out'    => $totalStockOut,
            'manual_adjustments' => $manualAdjustments,
        ];

        return response()->json([
            'summary' => $summary,

            // FIX: previously ->take(5) permanently capped this list at 5 rows
            // total, so it never grew with new activity and ignored the
            // DataTables "Show" page-length control. Recent Activity should
            // show the *same* full, newest-first dataset as Full History —
            // the table's own pagination (pageLength: 25, Show dropdown)
            // is what handles "recent" paging on the frontend.
            'recent_activity' => $inventoryLogs->values(),

            'top_adjusted_products' => $applyDateFilter(
    DB::table('inventory_logs')
)
                ->join('products', 'inventory_logs.ProductID', '=', 'products.ProductID')
                ->select(
                    'products.ProductID',
                    'products.ProductName',
                    'products.SKU',
                    DB::raw('COUNT(*) as adjustment_count'),
                    DB::raw('SUM(CASE WHEN type = "stock_in" THEN quantity ELSE 0 END) as total_stock_in'),
                    DB::raw('SUM(CASE WHEN type = "stock_out" THEN quantity ELSE 0 END) as total_stock_out')
                )
                ->groupBy('products.ProductID', 'products.ProductName', 'products.SKU')
                ->orderBy('adjustment_count', 'desc')
                ->limit($topLimit)
                ->get(),

            'inventory_logs' => $inventoryLogs,
        ], 200);
    }
}