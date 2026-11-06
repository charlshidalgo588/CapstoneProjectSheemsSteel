<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRemnant extends Model
{
    protected $table = 'product_remnants';
    protected $primaryKey = 'RemnantID';

    protected $fillable = [
        'ProductID', 'VariantID', 'VariantLabel', 'SaleID',
        'Quantity', 'Length', 'FullLength', 'Unit', 'BasePrice', 'Status',
    ];

    public function scopeAvailable($query)
    {
        return $query->where('Status', 'available');
    }
}