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
        /**
         * ============================================================
         * UNIFIED REPORTS ENDPOINT
         * ============================================================
         * Supports two modes, switched by ?mode=month|day:
         *
         *   ?mode=month&year=2026&month=6   (default if mode omitted)
         *   ?mode=day&date=2026-06-14
         *
         * Both modes return the exact same response shape — KPIs,
         * top selling items, and the sales + profit chart data — just
         * scoped to a month or to a single day. The frontend has one
         * picker that switches between the two; everything on the page
         * (KPI cards, Top Selling Items, the chart) reflects whichever
         * is active.
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

        /**
         * ============================================================
         * MONTH MODE
         * ============================================================
         */
        private function monthReport(Request $request)
        {
            // FIX (previous bug): year/month used to be hardcoded to 2025/12,
            // freezing the whole page on one historical month. Now both come
            // from the request, defaulting to the REAL current month.
            $year  = (int) $request->query('year', now()->year);
            $month = (int) $request->query('month', now()->month);

            // Clamp to plausible bounds — guards against garbage input like
            // ?month=0 or ?year=99999 producing a nonsense Carbon date.
            $month = max(1, min($month, 12));
            $year  = max(2000, min($year, (int) now()->year + 1));

            $periodStart = Carbon::create($year, $month, 1)->startOfMonth();

            $totalSales = DB::table('sales')
                ->whereYear('SaleDate', $year)
                ->whereMonth('SaleDate', $month)
                ->sum('TotalAmount') ?? 0;

            $itemsSold = DB::table('sales_items')
                ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
                ->whereYear('sales.SaleDate', $year)
                ->whereMonth('sales.SaleDate', $month)
                ->sum('sales_items.Quantity') ?? 0;

            $transactions = Sale::whereYear('SaleDate', $year)
                ->whereMonth('SaleDate', $month)
                ->count();

            $profit = DB::table('sales_items')
                ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
                ->join('products', 'sales_items.ProductID', '=', 'products.ProductID')
                ->whereYear('sales.SaleDate', $year)
                ->whereMonth('sales.SaleDate', $month)
                ->selectRaw('
                    SUM((sales_items.PriceAtSale - products.CostPrice) * sales_items.Quantity) as profit
                ')
                ->value('profit') ?? 0;

            $salesActivity = [
                ['label' => "This Month's Sales",      'value' => '₱' . number_format($totalSales, 2)],
                ['label' => "Items Sold This Month",    'value' => (int) $itemsSold],
                ['label' => "Transactions This Month",  'value' => (int) $transactions],
                ['label' => "Monthly Profit",           'value' => '₱' . number_format($profit, 2)],
            ];

            // FIX: same issue as day mode — an INNER join to `categories`
            // silently drops sales rows for any product whose CategoryID is
            // null or doesn't match a row in `categories`. Switched to
            // leftJoin + fallback label so this can't hide real sales data.
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

            // --- DAILY SALES --------------------------------------------------
            $rawSales = Sale::whereYear('SaleDate', $year)
                ->whereMonth('SaleDate', $month)
                ->select(
                    DB::raw('DATE(SaleDate) as date'),
                    DB::raw('SUM(TotalAmount) as total')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            // --- DAILY PROFIT ---------------------------------------------------
            // Same profit-per-day logic as HomeController: join sales_items to
            // products so we can compute (PriceAtSale - CostPrice) * Quantity,
            // grouped by day across the selected month.
            $rawProfit = DB::table('sales_items')
                ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
                ->join('products', 'sales_items.ProductID', '=', 'products.ProductID')
                ->whereYear('sales.SaleDate', $year)
                ->whereMonth('sales.SaleDate', $month)
                ->selectRaw('DATE(sales.SaleDate) as date,
                    SUM((sales_items.PriceAtSale - products.CostPrice) * sales_items.Quantity) as profit')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            // ----------------------------------------------------------------------
            // 🔥 Same alignment logic as HomeController: sales and profit are
            // keyed by date so both series can be paired up. Unlike
            // HomeController — which walks every calendar day in the range
            // and pads gaps with 0 — this only includes dates that actually
            // have a transaction (a sale, a profit-bearing line item, or
            // both). That's what naturally keeps future/not-yet-happened
            // days and inactive days out of the chart, while still
            // guaranteeing monthly_sales[i] and daily_profit[i] describe the
            // exact same date, index for index.
            // ----------------------------------------------------------------------
            $transactionDates = $rawSales->keys()
                ->merge($rawProfit->keys())
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
                    'profit' => $rawProfit[$date]->profit ?? 0,
                ];
            }

            return response()->json([
                'mode'              => 'month',
                'selected_period'   => [
                    'year'  => $year,
                    'month' => $month,
                    'value' => sprintf('%04d-%02d', $year, $month), // for <input type="month">
                    'label' => $periodStart->format('F Y'),         // e.g. "June 2026"
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

        /**
         * ============================================================
         * DAY MODE
         * ============================================================
         */
        private function dayReport(Request $request)
        {
            $request->validate([
                'date' => ['required', 'date_format:Y-m-d'],
            ]);

            $date = Carbon::createFromFormat('Y-m-d', $request->query('date'));

            // Guard against a future date — there can't be sales data yet.
            if ($date->isAfter(now()->endOfDay())) {
                $date = now();
            }

            $totalSales = DB::table('sales')
                ->whereDate('SaleDate', $date)
                ->sum('TotalAmount') ?? 0;

            $itemsSold = DB::table('sales_items')
                ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
                ->whereDate('sales.SaleDate', $date)
                ->sum('sales_items.Quantity') ?? 0;

            $transactions = Sale::whereDate('SaleDate', $date)->count();

            $profit = DB::table('sales_items')
                ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
                ->join('products', 'sales_items.ProductID', '=', 'products.ProductID')
                ->whereDate('sales.SaleDate', $date)
                ->selectRaw('
                    SUM((sales_items.PriceAtSale - products.CostPrice) * sales_items.Quantity) as profit
                ')
                ->value('profit') ?? 0;

            $salesActivity = [
                ['label' => "Sales This Day",        'value' => '₱' . number_format($totalSales, 2)],
                ['label' => "Items Sold This Day",    'value' => (int) $itemsSold],
                ['label' => "Transactions This Day",  'value' => (int) $transactions],
                ['label' => "Profit This Day",        'value' => '₱' . number_format($profit, 2)],
            ];

            // FIX: this previously used an INNER join to `categories`, which
            // silently dropped every sales row for a product whose CategoryID
            // is null or doesn't match any row in `categories` — even though
            // that sale clearly happened (the KPI cards above prove it, since
            // they never join categories at all). Switched to a leftJoin with
            // a fallback label so a missing/orphaned category can no longer
            // make real sales data disappear from this table.
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

            // --- HOURLY SALES ---------------------------------------------------
            // Chart: one point per HOUR across the single day, since a day
            // has no "days" to plot — this gives the owner a same-shape line
            // chart showing how sales moved through that specific day.
            $rawSales = Sale::whereDate('SaleDate', $date)
                ->select(
                    DB::raw('HOUR(SaleDate) as hour'),
                    DB::raw('SUM(TotalAmount) as total')
                )
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->keyBy('hour');

            // --- HOURLY PROFIT ---------------------------------------------------
            // Same profit computation as month mode / HomeController, just
            // bucketed by hour instead of by day, keyed for lookup below.
            $rawProfit = DB::table('sales_items')
                ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
                ->join('products', 'sales_items.ProductID', '=', 'products.ProductID')
                ->whereDate('sales.SaleDate', $date)
                ->selectRaw('HOUR(sales.SaleDate) as hour,
                    SUM((sales_items.PriceAtSale - products.CostPrice) * sales_items.Quantity) as profit')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->keyBy('hour');

            // 🔥 Same alignment logic as month mode: only include hours that
            // actually have a transaction (a sale, a profit-bearing line
            // item, or both) — no padded-in empty hours — while still
            // guaranteeing monthly_sales[i] and daily_profit[i] describe the
            // exact same hour, index for index.
            $transactionHours = $rawSales->keys()
                ->merge($rawProfit->keys())
                ->unique()
                ->sort()
                ->values();

            $monthlySales = [];
            $dailyProfit  = [];

            foreach ($transactionHours as $hour) {
                $label = sprintf('%02d:00', $hour); // reuse the same "date" key the chart already plots

                $monthlySales[] = [
                    'date'  => $label,
                    'total' => $rawSales[$hour]->total ?? 0,
                ];

                $dailyProfit[] = [
                    'date'   => $label,
                    'profit' => $rawProfit[$hour]->profit ?? 0,
                ];
            }

            return response()->json([
                'mode'              => 'day',
                'selected_period'   => [
                    'date'  => $date->format('Y-m-d'),
                    'value' => $date->format('Y-m-d'),   // for <input type="date">
                    'label' => $date->format('F j, Y'),  // e.g. "June 14, 2026"
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

        /**
         * ============================================================
         * INVENTORY SUMMARY
         * ============================================================
         * Point-in-time stock levels — not scoped to month or day in
         * either mode, since "how much stock exists right now" doesn't
         * change based on which sales period you're looking at.
         *
         * FIX: "Quantity to Receive" removed — it was hardcoded to 0 with
         * no backing query (no purchase_orders / incoming-stock table
         * referenced anywhere), so it could never reflect anything real.
         */
        private function inventorySummary(): array
{
    $quantityInHand = Inventory::sum('QuantityOnHand');

    // FIX: was comparing against products.ReorderLevel, but the dashboard
    // (HomeController) compares against inventories.ReorderLevel — a
    // different column that isn't guaranteed to hold the same value.
    // That mismatch was why this page showed a different Low Stock count
    // than the dashboard. Aligned to inventories.ReorderLevel to match
    // the known-accurate dashboard figure.
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

        /**
         * ============================================================
         * SALES LIST (TRANSACTIONS PAGE)
         * ============================================================
         * Left unscoped to month/day — feeds a separate transactions
         * page, not the Reports KPIs/chart above.
         */
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