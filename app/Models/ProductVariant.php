<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $table      = 'product_variants';
    protected $primaryKey = 'VariantID';

    // NOTE: QuantityOnHand is intentionally NOT fillable. Change it only
    // through App\Services\StockService so the product total stays in sync.
    protected $fillable = [
        'ProductID', 'Label', 'Specs', 'SellingPrice', 'CostPrice',
        'ReorderLevel',                       // <-- added
    ];

    protected $casts = [
        'Specs'          => 'array',
        'SellingPrice'   => 'float',
        'CostPrice'      => 'float',
        'QuantityOnHand' => 'integer',        // <-- added
        'ReorderLevel'   => 'integer',        // <-- added
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'ProductID', 'ProductID');
    }
}

/* Keep these two from your notes (not part of this file):

   1) In App\Models\Product:
        public function variants()
        {
            return $this->hasMany(ProductVariant::class, 'ProductID', 'ProductID')
                        ->orderBy('VariantID');
        }

   2) In App\Models\SalesItem $fillable: 'VariantID', 'VariantLabel'
*/