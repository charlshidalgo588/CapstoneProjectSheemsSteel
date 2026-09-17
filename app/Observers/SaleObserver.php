<?php

namespace App\Observers;

use App\Models\Sale;
use App\Models\Notification;

class SaleObserver
{
    private const NOTABLE_SALE_THRESHOLD = 2000;

    public function created(Sale $sale): void
    {
        if ($sale->TotalAmount < self::NOTABLE_SALE_THRESHOLD) {
            return;
        }

        Notification::create([
            'type' => 'sale',
            'title' => 'New sale completed',
            'description' => "Order #{$sale->SaleID} processed for ₱" . number_format($sale->TotalAmount, 2) . '.',
            'notifiable_type' => Sale::class,
            'notifiable_id' => $sale->SaleID,
        ]);
    }
}