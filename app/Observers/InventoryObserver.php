<?php

namespace App\Observers;

use App\Models\Inventory;
use App\Models\Notification;

class InventoryObserver
{
    public function updated(Inventory $inventory): void
    {
        if (! $inventory->wasChanged('QuantityOnHand')) {
            return;
        }

        $qty = $inventory->QuantityOnHand;
        $reorderLevel = $inventory->ReorderLevel;
        $product = $inventory->product; // loads Product via belongsTo

        if (! $product) {
            return;
        }

        if ($qty <= 0) {
            $this->notifyOnce($inventory, 'out_of_stock',
                'Out of stock',
                "{$product->ProductName} is now out of stock.");
        } elseif ($reorderLevel !== null && $qty <= $reorderLevel) {
            $this->notifyOnce($inventory, 'low_stock',
                'Low stock alert',
                "{$product->ProductName} has only {$qty} units left.");
        }
    }

    private function notifyOnce(Inventory $inventory, string $type, string $title, string $desc): void
    {
        $existing = Notification::where('notifiable_type', Inventory::class)
            ->where('notifiable_id', $inventory->InventoryID)
            ->where('type', $type)
            ->whereNull('read_at')
            ->exists();

        if ($existing) {
            return;
        }

        Notification::create([
            'type' => $type,
            'title' => $title,
            'description' => $desc,
            'notifiable_type' => Inventory::class,
            'notifiable_id' => $inventory->InventoryID,
        ]);
    }
}