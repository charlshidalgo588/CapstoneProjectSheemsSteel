<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\SalesItem;

class HomeController extends Controller
{
    /** Item profit = (price at sale - cost at sale) * quantity. */
    private const PROFIT_SQL = SalesItem::PROFIT_SQL;

    /**
     * Shop sales = what the customer paid MINUS the worker's pass-through share
     * of any extra services. Needs the `svc` subquery joined (see svcSub()).
     */
    private const NET_SALES_SQL = 'SUM(sales.TotalAmount - COALESCE(svc.worker_total, 0))';

    /** Per-sale worker total, to join onto `sales`. */
    private function svcSub()
    {
        return DB::raw('(SELECT SaleID, SUM(WorkerAmount) AS worker_total FROM sale_services GROUP BY SaleID) as svc');
    }

    /** sales + worker totals, ready to filter and sum. */
    private function netSalesQuery()
    {
        return DB::table('sales')->leftJoin($this->svcSub(), 'svc.SaleID', '=', 'sales.SaleID');
    }

    /**
     * Stock counts, SIZE by SIZE (every size is its own item).
     *   out     = QuantityOnHand <= 0
     *   low     = QuantityOnHand > 0 AND <= ReorderLevel (or the default when ReorderLevel is 0)
     *   healthy = everything else
     * "low" and "out" are SEPARATE and never overlap.
     * A product that has no sizes at all counts as ONE item (its own inventory row),
     * and uses the SAME low-stock rule as sizes.
     */
    private function stockSummary(): array
    {
        $default = StockService::DEFAULT_LOW_STOCK;

        // --- sizes ---
        $sizeLimit = 'CASE WHEN ReorderLevel > 0 THEN ReorderLevel ELSE ? END';

        $totalSizes = ProductVariant::count();

        $outSizes = ProductVariant::where('QuantityOnHand', '<=', 0)->count();

        $lowSizes = ProductVariant::where('QuantityOnHand', '>', 0)
            ->whereRaw("QuantityOnHand <= {$sizeLimit}", [$default])
            ->count();

        // --- products without any size (legacy) ---
        $legacy = DB::table('products')
            ->join('inventories', 'products.ProductID', '=', 'inventories.ProductID')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('product_variants')
                  ->whereColumn('product_variants.ProductID', 'products.ProductID');
            });

        $legacyLimit = 'CASE WHEN inventories.ReorderLevel > 0 THEN inventories.ReorderLevel ELSE ? END';

        $legacyTotal = (clone $legacy)->count();
        $legacyOut   = (clone $legacy)->where('inventories.QuantityOnHand', '<=', 0)->count();
        $legacyLow   = (clone $legacy)
            ->where('inventories.QuantityOnHand', '>', 0)
            ->whereRaw("inventories.QuantityOnHand <= {$legacyLimit}", [$default])
            ->count();

        $total = $totalSizes + $legacyTotal;
        $low   = $lowSizes + $legacyLow;
        $out   = $outSizes + $legacyOut;
        $needs = $low + $out;

        return [
            'low_stock_items'      => $low,                     // running low (NOT zero)
            'out_of_stock_items'   => $out,                     // zero stock
            'needs_restock_items'  => $needs,                   // low + out
            'total_items'          => $total,                   // every size
            'active_items'         => max($total - $needs, 0),  // healthy sizes
            'products_to_restock'  => Product::whereHas('variants', function ($v) use ($default, $sizeLimit) {
                $v->whereRaw("QuantityOnHand <= {$sizeLimit}", [$default]);
            })->count(),
        ];
    }

    public function dashboard(Request $request)
    {
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd   = Carbon::today()->endOfDay();

        // --- BASIC TODAY METRICS ---------------------------------------------
        // whereBetween (not whereDate) so an index on SaleDate can be used.
        $todaySales = $this->netSalesQuery()
            ->whereBetween('sales.SaleDate', [$todayStart, $todayEnd])
            ->selectRaw('ROUND(' . self::NET_SALES_SQL . ') as total')
            ->value('total') ?? 0;

        $itemsSoldToday = DB::table('sales_items')
            ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
            ->whereBetween('sales.SaleDate', [$todayStart, $todayEnd])
            ->sum('sales_items.Quantity');

        $transactionsToday = Sale::whereBetween('SaleDate', [$todayStart, $todayEnd])->count();

        // --- INVENTORY SUMMARY (per size) ------------------------------------
        $inventorySummary = array_merge([
            'quantity_in_hand'    => Inventory::sum('QuantityOnHand'),
            'quantity_to_receive' => 0,
        ], $this->stockSummary());

        // --- DATE RANGE HANDLING ---------------------------------------------
        $dateRange = $request->input('date_range', 'this_month');
        $startDate = $this->getStartDate($dateRange)->startOfDay();
        $endDate   = $this->getEndDate($dateRange)->endOfDay();

        // --- TOTAL PROFIT = item profit + the shop's share of services --------
        $itemProfit = DB::table('sales_items')
            ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
            ->whereBetween('sales.SaleDate', [$startDate, $endDate])
            ->selectRaw(self::PROFIT_SQL . ' as profit')
            ->value('profit') ?? 0;

        $serviceProfit = (float) DB::table('sale_services')
            ->join('sales', 'sale_services.SaleID', '=', 'sales.SaleID')
            ->whereBetween('sales.SaleDate', [$startDate, $endDate])
            ->sum('sale_services.OwnerAmount');

        $totalProfit = round((float) $itemProfit + $serviceProfit, 2);

        // --- PRODUCTS (feeds "Where sales come from") --------------------------
        // Same rows as the Reports page: EVERY product sold in the range, with
        // units, sales AND profit. The dashboard groups the tail into "Other".
        $topSellingItems = DB::table('sales_items')
            ->join('products', 'sales_items.ProductID', '=', 'products.ProductID')
            ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
            ->leftJoin('categories', 'products.CategoryID', '=', 'categories.CategoryID')
            ->whereBetween('sales.SaleDate', [$startDate, $endDate])
            ->select(
                'products.ProductID',
                'products.ProductName',
                DB::raw("COALESCE(categories.CategoryName, 'Uncategorized') as CategoryName"),
                DB::raw('SUM(sales_items.Quantity) as total_quantity'),
                DB::raw('SUM(sales_items.Quantity * sales_items.PriceAtSale) as total_sales'),
                DB::raw(self::PROFIT_SQL . ' as total_profit')
            )
            ->groupBy('products.ProductID', 'products.ProductName', 'categories.CategoryName')
            ->orderByDesc('total_quantity')
            ->get();

        // --- DAILY SALES (shop's share) ---------------------------------------
        $rawSales = $this->netSalesQuery()
            ->whereBetween('sales.SaleDate', [$startDate, $endDate])
            ->selectRaw('DATE(sales.SaleDate) as date, ' . self::NET_SALES_SQL . ' as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // --- DAILY TRANSACTION COUNT (drives the "busiest" heatmap) ------------
        // Same logic as the Reports page: number of sales per day bucket.
        $rawTx = DB::table('sales')
            ->whereBetween('sales.SaleDate', [$startDate, $endDate])
            ->selectRaw('DATE(sales.SaleDate) as date, COUNT(*) as tx')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // --- DAILY PROFIT: items + services ------------------------------------
        $rawItemProfit = DB::table('sales_items')
            ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
            ->whereBetween('sales.SaleDate', [$startDate, $endDate])
            ->selectRaw('DATE(sales.SaleDate) as date, ' . self::PROFIT_SQL . ' as profit')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $rawServiceProfit = DB::table('sale_services')
            ->join('sales', 'sale_services.SaleID', '=', 'sales.SaleID')
            ->whereBetween('sales.SaleDate', [$startDate, $endDate])
            ->selectRaw('DATE(sales.SaleDate) as date, SUM(sale_services.OwnerAmount) as profit')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // --- Align sales + transactions + profit over the full date range ------
        $monthlySales = [];
        $dailyProfit  = [];

        $period = new \DatePeriod(
            $startDate->copy()->startOfDay(),
            new \DateInterval('P1D'),
            $endDate->copy()->addDay()->startOfDay()
        );

        foreach ($period as $day) {
            $date = $day->format('Y-m-d');

            $monthlySales[] = [
                'date'         => $date,
                'total'        => $rawSales[$date]->total ?? 0,
                'transactions' => (int) ($rawTx[$date]->tx ?? 0),
            ];

            $dailyProfit[] = [
                'date'   => $date,
                'profit' => round(
                    (float) ($rawItemProfit[$date]->profit ?? 0) + (float) ($rawServiceProfit[$date]->profit ?? 0),
                    2
                ),
            ];
        }

        // TOTAL SALES RANGE (shop's share)
        $monthlyTotal = $this->netSalesQuery()
            ->whereBetween('sales.SaleDate', [$startDate, $endDate])
            ->selectRaw(self::NET_SALES_SQL . ' as total')
            ->value('total') ?? 0;

        // --- RETURN RESPONSE --------------------------------------------------
        return response()->json([
            'todaySales'        => $todaySales,
            'itemsSoldToday'    => $itemsSoldToday,
            'transactionsToday' => $transactionsToday,

            'inventorySummary'  => $inventorySummary,
            'topSellingItems'   => $topSellingItems,

            'monthlySales'      => $monthlySales,   // each day: date, total, transactions
            'dailyProfit'       => $dailyProfit,

            'monthlyTotal'      => $monthlyTotal,
            'totalProfit'       => $totalProfit,
            'dateRange'         => $dateRange,

            // The shop's share of extra services. Shown as its own "Extra services"
            // slice in the sales-mix donut (worker pay is already excluded).
            'service_sales'     => round($serviceProfit, 2),
            'service_profit'    => round($serviceProfit, 2),
        ]);
    }

    // ------------------ DATE RANGE HELPERS ---------------------
    // Weeks run Sunday -> Saturday, the same as the Reports page, so the
    // dashboard and Reports always agree on "this week".
    // The *NoOverflow variants stop e.g. Mar 31 - 1 month from landing in March.

    private function getStartDate($dateRange): Carbon
    {
        return match ($dateRange) {
            'today'      => Carbon::today(),
            'yesterday'  => Carbon::yesterday(),
            'this_week'  => Carbon::now()->startOfWeek(Carbon::SUNDAY),
            'last_week'  => Carbon::now()->subWeek()->startOfWeek(Carbon::SUNDAY),
            'this_month' => Carbon::now()->startOfMonth(),
            'last_month' => Carbon::now()->subMonthNoOverflow()->startOfMonth(),
            'this_year'  => Carbon::now()->startOfYear(),
            'last_year'  => Carbon::now()->subYearNoOverflow()->startOfYear(),
            default      => Carbon::now()->startOfMonth(),
        };
    }

    private function getEndDate($dateRange): Carbon
    {
        return match ($dateRange) {
            'today'      => Carbon::today()->endOfDay(),
            'yesterday'  => Carbon::yesterday()->endOfDay(),
            'this_week'  => Carbon::now()->endOfWeek(Carbon::SATURDAY),
            'last_week'  => Carbon::now()->subWeek()->endOfWeek(Carbon::SATURDAY),
            'this_month' => Carbon::now()->endOfMonth(),
            'last_month' => Carbon::now()->subMonthNoOverflow()->endOfMonth(),
            'this_year'  => Carbon::now()->endOfYear(),
            'last_year'  => Carbon::now()->subYearNoOverflow()->endOfYear(),
            default      => Carbon::now()->endOfMonth(),
        };
    }
}