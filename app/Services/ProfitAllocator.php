<?php

namespace App\Services;

/**
 * Proportional cost / profit allocation for cut pieces and leftovers (remnants).
 *
 * Rule: a whole piece has an expected profit of (list price - cost). Selling part of it
 * books only that share; the REST is computed as (total - booked), never as a second
 * rounded share, so booked + rest always equals the total to the cent.
 *
 *   6 m piece, profit 200  ->  sell 3 m: book 100, remnant keeps 100
 *                          ->  sell remnant: book 100          => 200 total
 *
 * No database access in here, so it can be unit tested on its own.
 */
final class ProfitAllocator
{
    public static function money($value): float
    {
        return round((float) $value, 2) + 0.0;   // + 0.0 turns -0.0 into 0.0
    }

    /**
     * Split a total cost + profit by $share (0..1).
     *
     * @return array{sold_cost: float, sold_profit: float, rest_cost: float, rest_profit: float}
     */
    public static function split(float $totalCost, float $totalProfit, float $share): array
    {
        $share = max(0.0, min(1.0, $share));

        $soldCost   = self::money($totalCost * $share);
        $soldProfit = self::money($totalProfit * $share);

        return [
            'sold_cost'   => $soldCost,
            'sold_profit' => $soldProfit,
            'rest_cost'   => self::money($totalCost - $soldCost),
            'rest_profit' => self::money($totalProfit - $soldProfit),
        ];
    }

    /**
     * A cut line: $qty whole pieces, each cut into sold_length / full_length.
     * Returns null when the line is not a valid cut (then the normal profit formula applies).
     *
     * @param array|null $cut ['full_length' => float, 'sold_length' => float]
     */
    public static function forCut(int $qty, float $unitCost, float $listPrice, ?array $cut): ?array
    {
        $full = (float) ($cut['full_length'] ?? 0);
        $sold = (float) ($cut['sold_length'] ?? 0);

        if ($qty < 1 || $full <= 0 || $sold <= 0) {
            return null;
        }

        $totalCost   = self::money($qty * $unitCost);
        $totalProfit = self::money($qty * ($listPrice - $unitCost));

        return self::split($totalCost, $totalProfit, $sold / $full);
    }

    /**
     * Selling $qty of the $have pieces held by a remnant row.
     * Selling the last pieces takes the whole remaining balance (no cent left behind).
     */
    public static function forRemnantUnits(float $remainingCost, float $remainingProfit, int $have, int $qty): array
    {
        $have = max($have, 1);

        if ($qty >= $have) {
            return [
                'sold_cost'   => self::money($remainingCost),
                'sold_profit' => self::money($remainingProfit),
                'rest_cost'   => 0.0,
                'rest_profit' => 0.0,
            ];
        }

        return self::split($remainingCost, $remainingProfit, $qty / $have);
    }
}