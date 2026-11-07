<?php

namespace App\Http\Controllers;

use App\Models\InventoryLog;
use App\Services\SaleTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class InventoryLogController extends Controller
{
    /** Name of the unit column on the `products` table. */
    private const UNIT_COLUMN = 'Unit';

    /** The POS writes this into the note of every cut sale. */
    private const CUT_NOTE_LIKE = '%(cut piece)%';

    /** Name of the sizes table, if the project has one. */
    private function variantTable(): ?string
    {
        return collect(['product_variants', 'variants'])
            ->first(fn ($t) => Schema::hasTable($t));
    }

    public function index(Request $request)
    {
        $topLimit = max(1, min((int) $request->query('top_limit', 10), 100));
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');
        $unitCol  = 'products.' . self::UNIT_COLUMN;

        $range = fn() => ($dateFrom && $dateTo)
            ? [Carbon::parse($dateFrom)->startOfDay(), Carbon::parse($dateTo)->endOfDay()]
            : null;

        $applyDateFilter = function ($query) use ($range) {
            if ($r = $range()) {
                $query->whereBetween('inventory_logs.created_at', $r);
            }
            return $query;
        };

        $hasIsCut = Schema::hasColumn('inventory_logs', 'is_cut');

        /**
         * A log row is a "cut" when is_cut = 1 OR its note says "(cut piece)".
         * The note fallback means cuts are counted even if the sale code forgot
         * to set is_cut (that was why the KPI showed 0).
         */
        $cutScope = function ($query) use ($hasIsCut) {
            return $query->where(function ($w) use ($hasIsCut) {
                if ($hasIsCut) {
                    $w->where('inventory_logs.is_cut', 1)
                      ->orWhere('inventory_logs.notes', 'like', self::CUT_NOTE_LIKE);
                } else {
                    $w->where('inventory_logs.notes', 'like', self::CUT_NOTE_LIKE);
                }
            });
        };

        $totalStockIn  = $applyDateFilter(DB::table('inventory_logs')->where('type', 'stock_in'))->sum('quantity');
        $totalStockOut = $applyDateFilter(DB::table('inventory_logs')->where('type', 'stock_out'))->sum('quantity');
        $totalAdjustments  = $applyDateFilter(DB::table('inventory_logs'))->count();
        $manualAdjustments = $applyDateFilter(DB::table('inventory_logs')->where('notes', 'not like', 'POS sale%'))->count();

        // Cut pieces sold in the period (whole pieces consumed by cut sales)
        $cutCount = $applyDateFilter(
            $cutScope(DB::table('inventory_logs')->where('inventory_logs.type', 'stock_out'))
        )->sum('quantity');

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
                ->groupBy('unit')->orderBy('unit')->get();
        };

        // Size label on each log row — only if the tables/columns exist.
        $variantTable = $this->variantTable();
        $withVariant = $variantTable
            && Schema::hasColumn('inventory_logs', 'VariantID')
            && Schema::hasColumn($variantTable, 'VariantID')
            && Schema::hasColumn($variantTable, 'Label');

        $hasLogLabel = Schema::hasColumn('inventory_logs', 'VariantLabel');
        $labelSelect = $withVariant
            ? DB::raw($hasLogLabel
                ? "COALESCE(inventory_logs.VariantLabel, {$variantTable}.Label) as VariantLabel"
                : "{$variantTable}.Label as VariantLabel")
            : ($hasLogLabel ? null : DB::raw('NULL as VariantLabel'));

        $inventoryLogs = $applyDateFilter(DB::table('inventory_logs'))
            ->join('products', 'inventory_logs.ProductID', '=', 'products.ProductID')
            ->join('categories', 'products.CategoryID', '=', 'categories.CategoryID')
            ->when($withVariant, fn ($q) => $q->leftJoin(
                $variantTable, 'inventory_logs.VariantID', '=', "{$variantTable}.VariantID"
            ))
            ->select(array_filter([
                'inventory_logs.*',
                'products.ProductName',
                'products.SKU',
                DB::raw("{$unitCol} as Unit"),
                'categories.CategoryName',
                $labelSelect,
            ]))
            ->orderBy('inventory_logs.created_at', 'desc')
            ->get()
            // Always send a reliable is_cut flag (older rows only have the note).
            ->map(function ($row) {
                $row->is_cut = (int) (
                    !empty($row->is_cut)
                    || str_contains((string) ($row->notes ?? ''), '(cut piece)')
                );
                return $row;
            });

        // ── EXTRA SERVICES (from sales in the same period) ──
        // Use a status column only if the sales table really has one.
        $statusCol = collect(['Status', 'status', 'SaleStatus', 'PaymentStatus'])
            ->first(fn ($c) => Schema::hasColumn('sales', $c));

        $services = DB::table('sale_services')
            ->join('sales', 'sale_services.SaleID', '=', 'sales.SaleID')
            ->when($statusCol, fn ($q) => $q->where(
                fn ($w) => $w->where("sales.{$statusCol}", 'Paid')->orWhereNull("sales.{$statusCol}")
            ))
            ->when($range(), fn ($q, $r) => $q->whereBetween('sales.SaleDate', $r))
            ->select('sale_services.*', 'sales.SaleDate')
            ->orderBy('sales.SaleDate', 'desc')
            ->get()
            ->map(fn ($row) => array_merge(
                SaleTracker::normalizeService($row),
                ['SaleDate' => Carbon::parse($row->SaleDate)->format('M d, Y')]
            ))
            ->values();

        $summary = [
            'total_adjustments'  => $totalAdjustments,
            'total_stock_in'     => $totalStockIn,
            'total_stock_out'    => $totalStockOut,
            'manual_adjustments' => $manualAdjustments,
            'stock_in_by_unit'   => $totalsByUnit('stock_in'),
            'stock_out_by_unit'  => $totalsByUnit('stock_out'),
            'cut_count'          => (int) $cutCount,
            'service_count'      => $services->count(),
            'service_revenue'    => round($services->sum('Fee'), 2),
            'worker_payout'      => round($services->sum('WorkerAmount'), 2),
            'owner_share'        => round($services->sum('OwnerAmount'), 2),
        ];

        return response()->json([
            'summary'         => $summary,
            'recent_activity' => $inventoryLogs->values(),
            'top_adjusted_products' => $applyDateFilter(DB::table('inventory_logs'))
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
            'inventory_logs'  => $inventoryLogs->values(),
            'services'        => $services,
        ], 200);
    }

    /**
     * GET /api/inventory/remnants
     * Every leftover piece (available, used, scrapped) for the Inventory page.
     * This is a current-state list, so it is NOT date filtered.
     * Available pieces come first, then the newest used / scrapped ones.
     */
    public function remnants()
    {
        if (!Schema::hasTable('product_remnants')) {
            return response()->json([]);
        }

        $unitCol      = 'products.' . self::UNIT_COLUMN;
        $variantTable = $this->variantTable();
        $withVariant  = $variantTable && Schema::hasColumn($variantTable, 'Label');

        $remLabel = $withVariant
            ? DB::raw("COALESCE(r.VariantLabel, {$variantTable}.Label) as VariantLabel")
            : DB::raw('r.VariantLabel as VariantLabel');

        $rows = DB::table('product_remnants as r')
            ->join('products', 'r.ProductID', '=', 'products.ProductID')
            ->when($withVariant, fn ($q) => $q->leftJoin(
                $variantTable, 'r.VariantID', '=', "{$variantTable}.VariantID"
            ))
            ->select(
                'r.*',
                'products.ProductName',
                'products.SKU',
                DB::raw("{$unitCol} as ProductUnit"),   // r.Unit stays the LENGTH unit (ft / m / in)
                $remLabel
            )
            ->orderByRaw("CASE r.Status WHEN 'available' THEN 0 ELSE 1 END")
            ->orderBy('r.created_at', 'desc')
            ->get();

        return response()->json($rows->values());
    }

    /**
     * PATCH /api/inventory/remnants/{id}
     * Marks a leftover piece as available / used / scrapped.
     */
    public function updateRemnant(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => 'required|in:available,used,scrapped',
        ]);

        if (!Schema::hasTable('product_remnants')) {
            return response()->json(['message' => 'Remnant tracking is not set up yet. Run the migration.'], 422);
        }

        $remnant = DB::table('product_remnants')->where('RemnantID', $id)->first();
        if (!$remnant) {
            return response()->json(['message' => 'Leftover piece not found.'], 404);
        }

        DB::table('product_remnants')->where('RemnantID', $id)->update([
            'Status'     => $data['status'],
            'updated_at' => now(),
        ]);

        // Leave a trace in the inventory history (shown on the Inventory Logs page).
        // Quantity is 0 because no WHOLE piece enters or leaves stock — only the
        // leftover's status changes. The leftover's length + unit are stored on the
        // log row (cut_remaining_length / cut_unit) so the history can show exactly
        // which leftover was used / scrapped / put back.
        // If your `type` column is an ENUM that rejects these values, the status
        // change above still stands; only the history row is skipped.
        $logged = true;
        $logError = null;
        if ($data['status'] !== $remnant->Status) {
            try {
                $len = rtrim(rtrim(number_format((float) $remnant->Length, 3, '.', ''), '0'), '.');
                $what = [
                    'used'      => 'marked as used',
                    'scrapped'  => 'scrapped',
                    'available' => 'put back as available',
                ][$data['status']];

                InventoryLog::create([
                    'ProductID'            => $remnant->ProductID,
                    'SaleID'               => $remnant->SaleID,
                    'VariantID'            => $remnant->VariantID,
                    'VariantLabel'         => $remnant->VariantLabel,
                    'type'                 => 'remnant_' . $data['status'],
                    'quantity'             => 0,
                    'is_cut'               => 0,
                    // leftover details, so the log can display them
                    'cut_remaining_length' => $remnant->Length,
                    'cut_unit'             => $remnant->Unit,
                    'notes'                => "Leftover {$len} {$remnant->Unit} (from sale #{$remnant->SaleID}) {$what}",
                    'created_by'           => Auth::id() ?? 1,
                ]);
            } catch (\Throwable $e) {
                $logged = false;
                $logError = config('app.debug') ? $e->getMessage() : null;
                Log::warning('Leftover history row skipped: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'logged'  => $logged,
            'log_error' => $logError,
            'remnant' => DB::table('product_remnants')->where('RemnantID', $id)->first(),
        ]);
    }
}