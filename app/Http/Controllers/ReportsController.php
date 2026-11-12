<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /**
     * Item profit.
     *  - cut-piece and leftover lines: AllocatedProfit (the proportional share booked on that line;
     *    the cut and its leftover add up to the piece's full profit, counted once)
     *  - every other line (and all older sales): (price at sale - cost at sale) * quantity
     */
    private const PROFIT_SQL = 'SUM(COALESCE(sales_items.AllocatedProfit, (sales_items.PriceAtSale - COALESCE(sales_items.CostAtSale, 0)) * sales_items.Quantity))';

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

    private function itemsQuery(array $p)
    {
        return DB::table('sales_items')
            ->join('sales', 'sales_items.SaleID', '=', 'sales.SaleID')
            ->whereBetween('sales.SaleDate', [$p['start'], $p['end']]);
    }

    private function servicesQuery(array $p)
    {
        return DB::table('sale_services')
            ->join('sales', 'sale_services.SaleID', '=', 'sales.SaleID')
            ->whereBetween('sales.SaleDate', [$p['start'], $p['end']]);
    }

    /**
     * UNIFIED REPORTS ENDPOINT
     *   ?mode=month&year=2026&month=6   (default)
     *   ?mode=week&start=2026-11-08&end=2026-11-14   (Sunday -> Saturday)
     *   ?mode=day&date=2026-06-14
     *   ?mode=year&year=2026
     *   &totals_only=1   -> just the sales total (previous-period comparison)
     *   &include_sales=0 -> skip the transactions list
     */
    public function index(Request $request)
    {
        $mode = $request->query('mode', 'month');
        $mode = in_array($mode, ['day', 'week', 'month', 'year'], true) ? $mode : 'month';

        $request->validate([
            'date'  => ['nullable', 'date_format:Y-m-d'],
            'start' => ['nullable', 'date_format:Y-m-d'],
            'end'   => ['nullable', 'date_format:Y-m-d'],
        ]);

        $p = $this->resolvePeriod($request, $mode);

        $totalSales = round((float) ($this->netSalesQuery()
            ->whereBetween('sales.SaleDate', [$p['start'], $p['end']])
            ->selectRaw(self::NET_SALES_SQL . ' as total')
            ->value('total') ?? 0), 2);

        if ($request->boolean('totals_only')) {
            return response()->json([
                'mode'            => $mode,
                'selected_period' => $p['selected'],
                'monthly_total'   => $totalSales,
            ], 200);
        }

        $itemsSold = (int) ($this->itemsQuery($p)->sum('sales_items.Quantity') ?? 0);

        $transactions = DB::table('sales')
            ->whereBetween('SaleDate', [$p['start'], $p['end']])
            ->count();

        $itemProfit = (float) ($this->itemsQuery($p)
            ->selectRaw(self::PROFIT_SQL . ' as profit')
            ->value('profit') ?? 0);

        $serviceProfit = (float) $this->servicesQuery($p)->sum('sale_services.OwnerAmount');

        $profit = round($itemProfit + $serviceProfit, 2);

        $w = $p['words'];
        $salesActivity = [
            ['label' => $w['sales'],  'value' => '₱' . number_format($totalSales, 2)],
            ['label' => $w['items'],  'value' => $itemsSold],
            ['label' => $w['tx'],     'value' => (int) $transactions],
            ['label' => $w['profit'], 'value' => '₱' . number_format($profit, 2)],
        ];

        $topSelling = $this->itemsQuery($p)
            ->join('products', 'sales_items.ProductID', '=', 'products.ProductID')
            ->leftJoin('categories', 'products.CategoryID', '=', 'categories.CategoryID')
            ->select(
                'products.ProductID',
                'products.ProductName',
                DB::raw("COALESCE(categories.CategoryName, 'Uncategorized') as CategoryName"),
                DB::raw('SUM(sales_items.Quantity) as total_quantity'),
                DB::raw('SUM(sales_items.Quantity * sales_items.PriceAtSale) as total_sales')
            )
            ->groupBy('products.ProductID', 'products.ProductName', 'categories.CategoryName')
            ->orderByDesc('total_quantity')
            ->get();

        // --- SERIES (hour / day / month buckets) ---
        $bucket = $p['bucket'];

        $rawSales = $this->netSalesQuery()
            ->whereBetween('sales.SaleDate', [$p['start'], $p['end']])
            ->selectRaw("$bucket as bucket, " . self::NET_SALES_SQL . ' as total')
            ->groupBy('bucket')
            ->get()
            ->keyBy(fn ($r) => (string) $r->bucket);

        $rawItemProfit = $this->itemsQuery($p)
            ->selectRaw("$bucket as bucket, " . self::PROFIT_SQL . ' as profit')
            ->groupBy('bucket')
            ->get()
            ->keyBy(fn ($r) => (string) $r->bucket);

        $rawServiceProfit = $this->servicesQuery($p)
            ->selectRaw("$bucket as bucket, SUM(sale_services.OwnerAmount) as profit")
            ->groupBy('bucket')
            ->get()
            ->keyBy(fn ($r) => (string) $r->bucket);

        // Only buckets that actually have a transaction, in chronological order.
        // Hour buckets are numeric ("9", "10") so they are sorted as numbers;
        // day / month buckets ("2026-11-08", "2026-11") sort correctly as strings.
        $keys = $rawSales->keys()
            ->merge($rawItemProfit->keys())
            ->merge($rawServiceProfit->keys())
            ->unique()
            ->sort(fn ($a, $b) => $mode === 'day'
                ? ((int) $a <=> (int) $b)
                : strcmp((string) $a, (string) $b))
            ->values();

        $monthlySales = [];
        $profitSeries = [];

        foreach ($keys as $key) {
            $label = $mode === 'day' ? sprintf('%02d:00', (int) $key) : $key;

            $monthlySales[] = [
                'date'  => $label,
                'total' => round((float) ($rawSales[$key]->total ?? 0), 2),
            ];

            $profitSeries[] = [
                'date'   => $label,
                'profit' => round(
                    (float) ($rawItemProfit[$key]->profit ?? 0) + (float) ($rawServiceProfit[$key]->profit ?? 0),
                    2
                ),
            ];
        }

        $payload = [
            'mode'              => $mode,
            'selected_period'   => $p['selected'],
            'sales_activity'    => $salesActivity,
            'inventory_summary' => $this->inventorySummary(),
            'top_selling'       => $topSelling,
            'monthly_sales'     => $monthlySales,
            'monthly_profit'    => $profitSeries,
            'daily_profit'      => $profitSeries,   // kept for older callers
            'monthly_total'     => $totalSales,
        ];

        if ($request->boolean('include_sales', true)) {
            $payload['sales'] = $this->salesList();
        }

        return response()->json($payload, 200);
    }

    /** "Nov 8 – 14, 2026" / "Oct 25 – Nov 1, 2026" / "Dec 28, 2026 – Jan 3, 2027" */
    private function formatWeekRange(Carbon $start, Carbon $end): string
    {
        if ($start->year !== $end->year) {
            return $start->format('M j, Y') . ' – ' . $end->format('M j, Y');
        }
        if ($start->month !== $end->month) {
            return $start->format('M j') . ' – ' . $end->format('M j, Y');
        }
        return $start->format('M j') . ' – ' . $end->format('j, Y');
    }

    /** Date range, bucket SQL, labels and response meta for the selected period. */
    private function resolvePeriod(Request $request, string $mode): array
    {
        if ($mode === 'day') {
            $date = Carbon::createFromFormat('Y-m-d', $request->query('date', now()->format('Y-m-d')))->startOfDay();
            if ($date->isAfter(now())) {
                $date = now()->startOfDay();
            }

            return [
                'start'    => $date->copy()->startOfDay(),
                'end'      => $date->copy()->endOfDay(),
                'bucket'   => 'HOUR(sales.SaleDate)',
                'selected' => [
                    'date'  => $date->format('Y-m-d'),
                    'value' => $date->format('Y-m-d'),
                    'label' => $date->format('F j, Y'),
                ],
                'words'    => [
                    'sales' => 'Sales This Day', 'items' => 'Items Sold This Day',
                    'tx' => 'Transactions This Day', 'profit' => 'Profit This Day',
                ],
            ];
        }

        if ($mode === 'week') {
            $anchor = Carbon::createFromFormat('Y-m-d', $request->query('start', now()->format('Y-m-d')))->startOfDay();

            // Weeks run Sunday -> Saturday (Carbon: Sunday = 0). Snap to the Sunday
            // so a stray date can never produce a shifted or partial week.
            $start = $anchor->copy()->subDays($anchor->dayOfWeek)->startOfDay();
            $end   = $start->copy()->addDays(6)->endOfDay();

            return [
                'start'    => $start,
                'end'      => $end,
                'bucket'   => 'DATE(sales.SaleDate)',
                'selected' => [
                    'start' => $start->format('Y-m-d'),
                    'end'   => $end->format('Y-m-d'),
                    'value' => $start->format('Y-m-d'),
                    'label' => $this->formatWeekRange($start, $end),
                ],
                'words'    => [
                    'sales' => "This Week's Sales", 'items' => 'Items Sold This Week',
                    'tx' => 'Transactions This Week', 'profit' => 'Weekly Profit',
                ],
            ];
        }

        $year = max(2000, min((int) $request->query('year', now()->year), (int) now()->year + 1));

        if ($mode === 'year') {
            $start = Carbon::create($year, 1, 1)->startOfYear();

            return [
                'start'    => $start,
                'end'      => $start->copy()->endOfYear(),
                'bucket'   => "DATE_FORMAT(sales.SaleDate, '%Y-%m')",
                'selected' => ['year' => $year, 'value' => (string) $year, 'label' => (string) $year],
                'words'    => [
                    'sales' => "This Year's Sales", 'items' => 'Items Sold This Year',
                    'tx' => 'Transactions This Year', 'profit' => 'Yearly Profit',
                ],
            ];
        }

        $month = max(1, min((int) $request->query('month', now()->month), 12));
        $start = Carbon::create($year, $month, 1)->startOfMonth();

        return [
            'start'    => $start,
            'end'      => $start->copy()->endOfMonth(),
            'bucket'   => 'DATE(sales.SaleDate)',
            'selected' => [
                'year'  => $year,
                'month' => $month,
                'value' => sprintf('%04d-%02d', $year, $month),
                'label' => $start->format('F Y'),
            ],
            'words'    => [
                'sales' => "This Month's Sales", 'items' => 'Items Sold This Month',
                'tx' => 'Transactions This Month', 'profit' => 'Monthly Profit',
            ],
        ];
    }

    /**
     * Stock counts, SIZE by SIZE (every size is its own item). Same rules as the Home dashboard.
     *   out     = QuantityOnHand <= 0
     *   low     = QuantityOnHand > 0 AND <= ReorderLevel (or the default when ReorderLevel is 0)
     *   healthy = everything else
     * "low" and "out" are SEPARATE and never overlap.
     * A product that has no sizes at all counts as ONE item (its own inventory row).
     */
    private function stockSummary(): array
    {
        $default = StockService::DEFAULT_LOW_STOCK;
        $limit   = 'CASE WHEN ReorderLevel > 0 THEN ReorderLevel ELSE ? END';

        // --- sizes ---
        $totalSizes = ProductVariant::count();

        $outSizes = ProductVariant::where('QuantityOnHand', '<=', 0)->count();

        $lowSizes = ProductVariant::where('QuantityOnHand', '>', 0)
            ->whereRaw("QuantityOnHand <= {$limit}", [$default])
            ->count();

        // --- products without any size (legacy) ---
        $legacy = DB::table('products')
            ->join('inventories', 'products.ProductID', '=', 'inventories.ProductID')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('product_variants')
                  ->whereColumn('product_variants.ProductID', 'products.ProductID');
            });

        $legacyTotal = (clone $legacy)->count();
        $legacyOut   = (clone $legacy)->where('inventories.QuantityOnHand', '<=', 0)->count();
        $legacyLow   = (clone $legacy)
            ->where('inventories.QuantityOnHand', '>', 0)
            ->whereColumn('inventories.QuantityOnHand', '<=', 'inventories.ReorderLevel')
            ->count();

        $total = $totalSizes + $legacyTotal;
        $low   = $lowSizes + $legacyLow;
        $out   = $outSizes + $legacyOut;

        return [
            'low'    => $low,
            'out'    => $out,
            'total'  => $total,
            'active' => max($total - $low - $out, 0), // healthy items
        ];
    }

    /** INVENTORY SUMMARY (point-in-time, not scoped to the period) */
    private function inventorySummary(): array
    {
        $s = $this->stockSummary();

        return [
            ['key' => 'quantity', 'label' => 'Quantity in Hand',   'value' => (int) Inventory::sum('QuantityOnHand')],
            ['key' => 'low',      'label' => 'Low Stock Items',    'value' => (int) $s['low'],   'isWarn' => true],
            ['key' => 'out',      'label' => 'Out of Stock Items', 'value' => (int) $s['out'],   'isRed' => true],
            ['key' => 'total',    'label' => 'Total Items',        'value' => (int) $s['total']],
            ['key' => 'active',   'label' => 'Active Items',       'value' => (int) $s['active']],
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