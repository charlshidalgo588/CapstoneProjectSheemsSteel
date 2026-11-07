<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesItem extends Model
{
    /** @use HasFactory<\Database\Factories\SalesItemFactory> */
    use HasFactory;

    protected $primaryKey = 'SalesItemID';

    protected $fillable = [
        'SaleID',
        'ProductID',
        'VariantID',
        'VariantLabel',
        'Quantity',
        'PriceAtSale',
        'CostAtSale',
        // Line TOTALS for cut-piece / leftover lines (NULL on normal lines).
        'AllocatedCost',
        'AllocatedProfit',
    ];

    protected $casts = [
        'Quantity'        => 'integer',
        'PriceAtSale'     => 'decimal:2',
        'CostAtSale'      => 'decimal:2',
        'AllocatedCost'   => 'decimal:2',
        'AllocatedProfit' => 'decimal:2',
    ];

    /**
     * Cost of what was actually sold.
     * A cut piece only carries its share of the full piece's cost:
     * cost x (length sold / full length).
     */
    public static function costFor(float $fullCost, ?array $cut = null): float
    {
        $full = (float) ($cut['full_length'] ?? 0);
        $sold = (float) ($cut['sold_length'] ?? 0);

        if ($full > 0 && $sold > 0) {
            return round($fullCost * min($sold / $full, 1), 2);
        }

        return round($fullCost, 2);
    }

    /** Profit of this line: the allocated profit when set, else (price - cost) x qty. */
    public function lineProfit(): float
    {
        if ($this->AllocatedProfit !== null) {
            return (float) $this->AllocatedProfit;
        }

        return round(((float) $this->PriceAtSale - (float) ($this->CostAtSale ?? 0)) * (int) $this->Quantity, 2);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'ProductID', 'ProductID');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'SaleID', 'SaleID');
    }
}