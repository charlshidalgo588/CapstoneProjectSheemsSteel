<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /** Item profit = (price at sale - cost at sale) * quantity. */
    private const PROFIT_SQL = 'SUM((sales_items.PriceAtSale - COALESCE(sales_items.CostAtSale, 0)) * sales_items.Quantity)';

    /** Shop sales = customer paid minus the worker's pass-through share of services. */
    private const NET_SALES_SQL = 'SUM(sales.TotalAmount - COALESCE(svc.worker_total, 0))';

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
     * UNIFIED REPORTS ENDPOINT
     *   ?mode=month&year=2026&month=6   (default)
     *   ?mode=day&date=2026-06-14
     */
    public function index(Request $request)
    {
        $mode = $request->query('mode', 'month');
        $mode = in_array($mode, ['month', 'day']) ? $mode : 'month';

        if ($mode === 'day') {
            return $this->dayReport($request);
        }

        return $this->monthReport($request);
    }

    /** MONTH MODE */
    private function monthReport(Request $request)
    {
        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $month = max(1, min($month, 12));
        $year  = max(2000, min($year, (int) now()->year + 1));

        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();

        $totalSales = $this->netSalesQuery()
            ->whereYear('sales.SaleDate', $year)
            ->whereMonth('sales.SaleDate', $month)
            ->selectRaw(self::NET_SALES_SQL . ' as total')
            ->value('total') ?? 0;

        $itemsSold = DB::table('sales_items')
            ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
            ->whereYear('sales.SaleDate', $year)
            ->whereMonth('sales.SaleDate', $month)
            ->sum('sales_items.Quantity') ?? 0;

        $transactions = Sale::whereYear('SaleDate', $year)
            ->whereMonth('SaleDate', $month)
            ->count();

        $itemProfit = DB::table('sales_items')
            ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
            ->whereYear('sales.SaleDate', $year)
            ->whereMonth('sales.SaleDate', $month)
            ->selectRaw(self::PROFIT_SQL . ' as profit')
            ->value('profit') ?? 0;

        $serviceProfit = DB::table('sale_services')
            ->join('sales', 'sale_services.SaleID', '=', 'sales.SaleID')
            ->whereYear('sales.SaleDate', $year)
            ->whereMonth('sales.SaleDate', $month)
            ->sum('sale_services.OwnerAmount');

        $profit = round((float) $itemProfit + (float) $serviceProfit, 2);

        $salesActivity = [
            ['label' => "This Month's Sales",      'value' => '₱' . number_format($totalSales, 2)],
            ['label' => "Items Sold This Month",   'value' => (int) $itemsSold],
            ['label' => "Transactions This Month", 'value' => (int) $transactions],
            ['label' => "Monthly Profit",          'value' => '₱' . number_format($profit, 2)],
        ];

        $topSelling = DB::table('sales_items')
            ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
            ->join('products', 'sales_items.ProductID', '=', 'products.ProductID')
            ->leftJoin('categories', 'products.CategoryID', '=', 'categories.CategoryID')
            ->whereYear('sales.SaleDate', $year)
            ->whereMonth('sales.SaleDate', $month)
            ->select(
                'products.ProductName',
                DB::raw("COALESCE(categories.CategoryName, 'Uncategorized') as CategoryName"),
                DB::raw('SUM(sales_items.Quantity) as total_quantity'),
                DB::raw('SUM(sales_items.Quantity * sales_items.PriceAtSale) as total_sales')
            )
            ->groupBy('products.ProductName', 'categories.CategoryName')
            ->orderByDesc('total_quantity')
            ->get();

        // --- DAILY SALES (shop's share) ---
        $rawSales = $this->netSalesQuery()
            ->whereYear('sales.SaleDate', $year)
            ->whereMonth('sales.SaleDate', $month)
            ->selectRaw('DATE(sales.SaleDate) as date, ' . self::NET_SALES_SQL . ' as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // --- DAILY PROFIT: items + services ---
        $rawItemProfit = DB::table('sales_items')
            ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
            ->whereYear('sales.SaleDate', $year)
            ->whereMonth('sales.SaleDate', $month)
            ->selectRaw('DATE(sales.SaleDate) as date, ' . self::PROFIT_SQL . ' as profit')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $rawServiceProfit = DB::table('sale_services')
            ->join('sales', 'sale_services.SaleID', '=', 'sales.SaleID')
            ->whereYear('sales.SaleDate', $year)
            ->whereMonth('sales.SaleDate', $month)
            ->selectRaw('DATE(sales.SaleDate) as date, SUM(sale_services.OwnerAmount) as profit')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // Only dates that actually have a transaction, aligned index for index.
        $transactionDates = $rawSales->keys()
            ->merge($rawItemProfit->keys())
            ->merge($rawServiceProfit->keys())
            ->unique()
            ->sort()
            ->values();

        $monthlySales = [];
        $dailyProfit  = [];

        foreach ($transactionDates as $date) {
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

        return response()->json([
            'mode'              => 'month',
            'selected_period'   => [
                'year'  => $year,
                'month' => $month,
                'value' => sprintf('%04d-%02d', $year, $month),
                'label' => $periodStart->format('F Y'),
            ],
            'sales_activity'    => $salesActivity,
            'inventory_summary' => $this->inventorySummary(),
            'top_selling'       => $topSelling,
            'monthly_sales'     => $monthlySales,
            'daily_profit'      => $dailyProfit,
            'monthly_total'     => $totalSales,
            'sales'             => $this->salesList(),
        ], 200);
    }

    /** DAY MODE */
    private function dayReport(Request $request)
    {
        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::createFromFormat('Y-m-d', $request->query('date'));

        if ($date->isAfter(now()->endOfDay())) {
            $date = now();
        }

        $totalSales = $this->netSalesQuery()
            ->whereDate('sales.SaleDate', $date)
            ->selectRaw(self::NET_SALES_SQL . ' as total')
            ->value('total') ?? 0;

        $itemsSold = DB::table('sales_items')
            ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
            ->whereDate('sales.SaleDate', $date)
            ->sum('sales_items.Quantity') ?? 0;

        $transactions = Sale::whereDate('SaleDate', $date)->count();

        $itemProfit = DB::table('sales_items')
            ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
            ->whereDate('sales.SaleDate', $date)
            ->selectRaw(self::PROFIT_SQL . ' as profit')
            ->value('profit') ?? 0;

        $serviceProfit = DB::table('sale_services')
            ->join('sales', 'sale_services.SaleID', '=', 'sales.SaleID')
            ->whereDate('sales.SaleDate', $date)
            ->sum('sale_services.OwnerAmount');

        $profit = round((float) $itemProfit + (float) $serviceProfit, 2);

        $salesActivity = [
            ['label' => "Sales This Day",        'value' => '₱' . number_format($totalSales, 2)],
            ['label' => "Items Sold This Day",   'value' => (int) $itemsSold],
            ['label' => "Transactions This Day", 'value' => (int) $transactions],
            ['label' => "Profit This Day",       'value' => '₱' . number_format($profit, 2)],
        ];

        $topSelling = DB::table('sales_items')
            ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
            ->join('products', 'sales_items.ProductID', '=', 'products.ProductID')
            ->leftJoin('categories', 'products.CategoryID', '=', 'categories.CategoryID')
            ->whereDate('sales.SaleDate', $date)
            ->select(
                'products.ProductName',
                DB::raw("COALESCE(categories.CategoryName, 'Uncategorized') as CategoryName"),
                DB::raw('SUM(sales_items.Quantity) as total_quantity'),
                DB::raw('SUM(sales_items.Quantity * sales_items.PriceAtSale) as total_sales')
            )
            ->groupBy('products.ProductName', 'categories.CategoryName')
            ->orderByDesc('total_quantity')
            ->get();

        // --- HOURLY SALES (shop's share) ---
        $rawSales = $this->netSalesQuery()
            ->whereDate('sales.SaleDate', $date)
            ->selectRaw('HOUR(sales.SaleDate) as hour, ' . self::NET_SALES_SQL . ' as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        // --- HOURLY PROFIT: items + services ---
        $rawItemProfit = DB::table('sales_items')
            ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
            ->whereDate('sales.SaleDate', $date)
            ->selectRaw('HOUR(sales.SaleDate) as hour, ' . self::PROFIT_SQL . ' as profit')
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');

        $rawServiceProfit = DB::table('sale_services')
            ->join('sales', 'sale_services.SaleID', '=', 'sales.SaleID')
            ->whereDate('sales.SaleDate', $date)
            ->selectRaw('HOUR(sales.SaleDate) as hour, SUM(sale_services.OwnerAmount) as profit')
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');

        $transactionHours = $rawSales->keys()
            ->merge($rawItemProfit->keys())
            ->merge($rawServiceProfit->keys())
            ->unique()
            ->sort()
            ->values();

        $monthlySales = [];
        $dailyProfit  = [];

        foreach ($transactionHours as $hour) {
            $label = sprintf('%02d:00', $hour);

            $monthlySales[] = [
                'date'  => $label,
                'total' => $rawSales[$hour]->total ?? 0,
            ];

            $dailyProfit[] = [
                'date'   => $label,
                'profit' => round(
                    (float) ($rawItemProfit[$hour]->profit ?? 0) + (float) ($rawServiceProfit[$hour]->profit ?? 0),
                    2
                ),
            ];
        }

        return response()->json([
            'mode'              => 'day',
            'selected_period'   => [
                'date'  => $date->format('Y-m-d'),
                'value' => $date->format('Y-m-d'),
                'label' => $date->format('F j, Y'),
            ],
            'sales_activity'    => $salesActivity,
            'inventory_summary' => $this->inventorySummary(),
            'top_selling'       => $topSelling,
            'monthly_sales'     => $monthlySales,
            'daily_profit'      => $dailyProfit,
            'monthly_total'     => $totalSales,
            'sales'             => $this->salesList(),
        ], 200);
    }

    /** INVENTORY SUMMARY (point-in-time, not scoped to the period) */
    private function inventorySummary(): array
    {
        $quantityInHand = Inventory::sum('QuantityOnHand');

        $lowStock = DB::table('products')
            ->join('inventories', 'products.ProductID', '=', 'inventories.ProductID')
            ->whereColumn('inventories.QuantityOnHand', '<=', 'inventories.ReorderLevel')
            ->count();

        return [
            ['label' => 'Quantity in Hand', 'value' => (int) $quantityInHand],
            ['label' => 'Low Stock Items',  'value' => (int) $lowStock, 'isRed' => true],
            ['label' => 'Total Items',      'value' => Product::count()],
            [
                'label' => 'Active Items',
                'value' => Product::whereHas('inventory', fn ($q) =>
                    $q->where('QuantityOnHand', '>', 0)
                )->count(),
            ],
        ];
    }

    /** SALES LIST (TRANSACTIONS PAGE): shows what the customer paid. */
    private function salesList()
    {
        return Sale::with(['customer', 'clerk'])
            ->orderBy('SaleDate', 'desc')
            ->get()
            ->map(function ($sale) {
                return [
                    'SaleID'        => $sale->SaleID,
                    'SaleDate'      => $sale->SaleDate,
                    'CustomerName'  => $sale->customer->CustomerCode ?? 'Walk-in Customer',
                    'TotalAmount'   => $sale->TotalAmount,
                    'PaymentMethod' => $sale->PaymentMethod,
                    'ClerkName'     => $sale->clerk->name ?? 'Unknown',
                ];
            });
    }
}