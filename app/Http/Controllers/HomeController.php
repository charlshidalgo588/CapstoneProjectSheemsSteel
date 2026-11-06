<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomeController extends Controller
{
    /** Item profit = (price at sale - cost at sale) * quantity. */
    private const PROFIT_SQL = 'SUM((sales_items.PriceAtSale - COALESCE(sales_items.CostAtSale, 0)) * sales_items.Quantity)';

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

    public function dashboard(Request $request)
    {
        $today = Carbon::today();

        // --- BASIC TODAY METRICS ---------------------------------------------
        $todaySales = $this->netSalesQuery()
            ->whereDate('sales.SaleDate', $today)
            ->selectRaw('ROUND(' . self::NET_SALES_SQL . ') as total')
            ->value('total') ?? 0;

        $itemsSoldToday = DB::table('sales_items')
            ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
            ->whereDate('sales.SaleDate', $today)
            ->sum('sales_items.Quantity');

        $transactionsToday = Sale::whereDate('SaleDate', $today)->count();

        // --- INVENTORY SUMMARY -----------------------------------------------
        $inventorySummary = [
            'quantity_in_hand'    => Inventory::sum('QuantityOnHand'),
            'quantity_to_receive' => 0,

            'low_stock_items'     => DB::table('products')
                ->join('inventories', 'products.ProductID', '=', 'inventories.ProductID')
                ->whereColumn('inventories.QuantityOnHand', '<=', 'inventories.ReorderLevel')
                ->count(),

            'total_items'         => Product::count(),
            'active_items'        => Product::whereHas('inventory', function ($q) {
                $q->where('QuantityOnHand', '>', 0);
            })->count(),
        ];

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

        $serviceProfit = DB::table('sale_services')
            ->join('sales', 'sale_services.SaleID', '=', 'sales.SaleID')
            ->whereBetween('sales.SaleDate', [$startDate, $endDate])
            ->sum('sale_services.OwnerAmount');

        $totalProfit = round((float) $itemProfit + (float) $serviceProfit, 2);

        // --- TOP SELLING ITEMS ------------------------------------------------
        $topSellingItems = DB::table('sales_items')
            ->join('products', 'sales_items.ProductID', '=', 'products.ProductID')
            ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
            ->leftJoin('categories', 'products.CategoryID', '=', 'categories.CategoryID')
            ->whereBetween('sales.SaleDate', [$startDate, $endDate])
            ->select(
                'products.ProductName',
                'categories.CategoryName',
                DB::raw('SUM(sales_items.Quantity) as total_quantity'),
                DB::raw('SUM(sales_items.Quantity * sales_items.PriceAtSale) as total_sales')
            )
            ->groupBy('products.ProductID', 'products.ProductName', 'categories.CategoryName')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->get();

        // --- DAILY SALES (shop's share) ---------------------------------------
        $rawSales = $this->netSalesQuery()
            ->whereBetween('sales.SaleDate', [$startDate, $endDate])
            ->selectRaw('DATE(sales.SaleDate) as date, ' . self::NET_SALES_SQL . ' as total')
            ->groupBy('date')
            ->orderBy('date')
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

        // --- Align sales + profit over the full date range --------------------
        $monthlySales = [];
        $dailyProfit  = [];

        $period = new \DatePeriod(
            Carbon::parse($startDate),
            new \DateInterval('P1D'),
            Carbon::parse($endDate)->addDay()
        );

        foreach ($period as $day) {
            $date = $day->format('Y-m-d');

            $monthlySales[] = [
                'date'  => $date,
                'total' => $rawSales[$date]->total ?? 0,
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

            'monthlySales'      => $monthlySales,
            'dailyProfit'       => $dailyProfit,

            'monthlyTotal'      => $monthlyTotal,
            'totalProfit'       => $totalProfit,
            'dateRange'         => $dateRange,
        ]);
    }

    // ------------------ DATE RANGE HELPERS ---------------------

    private function getStartDate($dateRange)
    {
        return match ($dateRange) {
            'today'      => Carbon::today(),
            'yesterday'  => Carbon::yesterday(),
            'this_week'  => Carbon::now()->startOfWeek(),
            'last_week'  => Carbon::now()->subWeek()->startOfWeek(),
            'this_month' => Carbon::now()->startOfMonth(),
            'last_month' => Carbon::now()->subMonth()->startOfMonth(),
            'this_year'  => Carbon::now()->startOfYear(),
            'last_year'  => Carbon::now()->subYear()->startOfYear(),
            default      => Carbon::now()->startOfMonth(),
        };
    }

    private function getEndDate($dateRange)
    {
        return match ($dateRange) {
            'today'      => Carbon::today()->endOfDay(),
            'yesterday'  => Carbon::yesterday()->endOfDay(),
            'this_week'  => Carbon::now()->endOfWeek(),
            'last_week'  => Carbon::now()->subWeek()->endOfWeek(),
            'this_month' => Carbon::now()->endOfMonth(),
            'last_month' => Carbon::now()->subMonth()->endOfMonth(),
            'this_year'  => Carbon::now()->endOfYear(),
            'last_year'  => Carbon::now()->subYear()->endOfYear(),
            default      => Carbon::now()->endOfMonth(),
        };
    }
}