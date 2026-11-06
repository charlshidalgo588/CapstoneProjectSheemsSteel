<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends Model
{
    protected $primaryKey = 'LogID';   // see the note below: make sure this matches the real column

    protected $fillable = [
        'ProductID',
        'SaleID',
        'VariantID',
        'VariantLabel',
        'type',
        'quantity',
        'notes',
        'is_cut',
        'cut_full_length',
        'cut_sold_length',
        'cut_remaining_length',
        'cut_unit',
        'created_by',
    ];

    protected $casts = [
        'is_cut'               => 'boolean',
        'cut_full_length'      => 'float',
        'cut_sold_length'      => 'float',
        'cut_remaining_length' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'ProductID', 'ProductID');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}