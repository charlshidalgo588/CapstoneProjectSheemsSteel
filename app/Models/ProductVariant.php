<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $table      = 'product_variants';
    protected $primaryKey = 'VariantID';

    protected $fillable = [
        'ProductID', 'Label', 'Specs', 'SellingPrice', 'CostPrice',
    ];

    protected $casts = [
        'Specs'        => 'array',
        'SellingPrice' => 'float',
        'CostPrice'    => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'ProductID', 'ProductID');
    }
}

/* ─────────────────────────────────────────────────────────────
   ADD THIS METHOD INSIDE YOUR EXISTING App\Models\Product CLASS:

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'ProductID', 'ProductID')
                    ->orderBy('VariantID');
    }

   ALSO: if your SalesItem model uses $fillable, add
   'VariantID' and 'VariantLabel' to it.
───────────────────────────────────────────────────────────── */