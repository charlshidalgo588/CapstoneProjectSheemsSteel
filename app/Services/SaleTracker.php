<?php

namespace App\Services;

use App\Models\InventoryLog;
use App\Models\ProductRemnant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SaleTracker
{
    /**
     * Writes the inventory log row for one sold line.
     * If the line was a cut piece, the log row carries the cut details and the
     * leftover length is saved as an offcut in product_remnants.
     *
     * Stock accounting for a cut: the WHOLE piece leaves normal stock
     * (StockService::deduct, qty = pieces cut). The leftover is tracked
     * separately as an offcut, so nothing disappears.
     *
     * The offcut also gets a BasePrice (baseline for resale):
     *   list price of the uncut piece - price the cut was sold for   (e.g. 370 - 235 = 135).
     * It only pre-fills the price in the POS; the cashier can still change it.
     *
     * PROFIT ALLOCATION: $alloc (from ProfitAllocator::forCut) holds the cost/profit that
     * was NOT booked on the cut sale. It is stored on the offcut (RemainingCost /
     * RemainingProfit) and booked later, once, when the offcut is sold.
     */
    public static function logItem(int $saleId, array $item, $variant, $product, ?int $userId, ?array $alloc = null): void
    {
        $cut   = $item['cut'] ?? null;
        $isCut = is_array($cut)
            && (float) ($cut['full_length'] ?? 0) > 0
            && (float) ($cut['sold_length'] ?? 0) > 0;

        $full = $isCut ? (float) $cut['full_length'] : null;
        $sold = $isCut ? min((float) $cut['sold_length'], $full) : null;
        $left = $isCut ? max($full - $sold, 0) : null;
        $unit = $isCut ? ($cut['unit'] ?? null) : null;
        $qty  = (int) $item['quantity'];

        InventoryLog::create([
            'ProductID'            => $item['product_id'],
            'SaleID'               => $saleId,
            'VariantID'            => $variant->VariantID,
            'VariantLabel'         => $variant->Label,
            'type'                 => 'stock_out',
            'quantity'             => $qty,
            // keep the "POS sale" prefix: the manual-adjustment count filters on it
            'notes'                => 'POS sale #' . $saleId . ($isCut ? ' (cut piece)' : ''),
            'is_cut'               => $isCut,
            'cut_full_length'      => $full,
            'cut_sold_length'      => $sold,
            'cut_remaining_length' => $left,
            'cut_unit'             => $unit,
            'created_by'           => $userId ?? 1,
        ]);

        if ($isCut && $left > 0) {
            $data = [
                'ProductID'    => $item['product_id'],
                'VariantID'    => $variant->VariantID,
                'VariantLabel' => $variant->Label,
                'SaleID'       => $saleId,
                'Quantity'     => $qty,
                'Length'       => $left,
                'FullLength'   => $full,   // used by the POS to suggest a price + book the cost
                'Unit'         => $unit,
                'Status'       => 'available',
            ];

            // Baseline price = uncut list price - price this cut was sold for.
            if (Schema::hasColumn('product_remnants', 'BasePrice')) {
                $base = round(max(0, (float) ($item['original_price'] ?? 0) - (float) ($item['price'] ?? 0)), 2);
                $data['BasePrice'] = $base > 0 ? $base : null;
            }

            // Cost + profit still to be booked when this leftover is sold.
            if ($alloc !== null) {
                $data['RemainingCost']   = $alloc['rest_cost'];
                $data['RemainingProfit'] = $alloc['rest_profit'];
            }

            // forceFill: the new columns work even if the model's $fillable doesn't list them.
            $remnant = new ProductRemnant();
            $remnant->forceFill($data)->save();
        }
    }

    /** When a sale is deleted its unused offcuts go with it (full-piece stock is restored separately). */
    public static function rollback(int $saleId): void
    {
        ProductRemnant::where('SaleID', $saleId)->where('Status', 'available')->delete();
    }

    /** One sale_services row -> clean array with the worker / owner split. */
    public static function normalizeService($row): array
    {
        $r   = (array) $row;
        $get = fn(array $keys) => collect($keys)->map(fn($k) => $r[$k] ?? null)->first(fn($v) => $v !== null);

        $fee    = (float) ($get(['Fee', 'fee']) ?? 0);
        $type   = $get(['WorkerShareType', 'worker_share_type']);
        $value  = (float) ($get(['WorkerShareValue', 'worker_share_value']) ?? 0);
        $stored = $get(['WorkerAmount', 'worker_amount']);

        $worker = $stored !== null
            ? (float) $stored
            : ($type === 'percent' ? $fee * $value / 100 : ($type === 'amount' ? $value : 0));
        $worker = max(0, min($worker, $fee));
        $owner  = $fee - $worker;

        return [
            'SaleID'       => $get(['SaleID', 'sale_id']),
            'Description'  => $get(['Description', 'description']) ?? '',
            'Fee'          => round($fee, 2),
            'WorkerName'   => $get(['WorkerName', 'worker_name']) ?: '—',
            'WorkerAmount' => round($worker, 2),
            'WorkerPct'    => $fee > 0 ? round($worker / $fee * 100, 2) : 0,
            'OwnerAmount'  => round($owner, 2),
            'OwnerPct'     => $fee > 0 ? round($owner / $fee * 100, 2) : 0,
        ];
    }

    public static function servicesFor(int $saleId): array
    {
        return DB::table('sale_services')->where('SaleID', $saleId)->get()
            ->map(fn($r) => self::normalizeService($r))->all();
    }

    /** [SaleID => total worker payout] for a list of sales (used by the sales list totals). */
    public static function workerPayouts(array $saleIds): array
    {
        if (!$saleIds) return [];
        $out = [];
        DB::table('sale_services')->whereIn('SaleID', $saleIds)->get()
            ->each(function ($r) use (&$out) {
                $n = self::normalizeService($r);
                $out[$n['SaleID']] = ($out[$n['SaleID']] ?? 0) + $n['WorkerAmount'];
            });
        return $out;
    }
}