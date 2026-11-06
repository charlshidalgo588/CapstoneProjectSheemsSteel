<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleService extends Model
{
    protected $table      = 'sale_services';
    protected $primaryKey = 'SaleServiceID';

    protected $fillable = [
        'SaleID', 'Description', 'Fee', 'WorkerName',
        'WorkerShareType', 'WorkerShareValue', 'WorkerAmount', 'OwnerAmount',
    ];

    protected $casts = [
        'Fee'              => 'decimal:2',
        'WorkerShareValue' => 'decimal:2',
        'WorkerAmount'     => 'decimal:2',
        'OwnerAmount'      => 'decimal:2',
    ];

    /**
     * Saves the service lines of a sale. The split is recomputed here
     * (owner = fee - worker) so the server never trusts the browser's math.
     */
    public static function record(int $saleId, array $services): void
    {
        foreach ($services as $svc) {
            $fee    = round(max((float) ($svc['fee'] ?? 0), 0), 2);
            $worker = round(min(max((float) ($svc['worker_amount'] ?? 0), 0), $fee), 2);

            self::create([
                'SaleID'           => $saleId,
                'Description'      => $svc['description'],
                'Fee'              => $fee,
                'WorkerName'       => $svc['worker_name'] ?? null,
                'WorkerShareType'  => $svc['worker_share_type'] ?? null,
                'WorkerShareValue' => (float) ($svc['worker_share_value'] ?? 0),
                'WorkerAmount'     => $worker,
                'OwnerAmount'      => round($fee - $worker, 2),
            ]);
        }
    }
}