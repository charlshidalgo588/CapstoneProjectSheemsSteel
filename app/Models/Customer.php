<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    protected $primaryKey = 'CustomerID';

    protected $fillable = [
        'CustomerCode',
        'CustomerName',
        'ContactNumber',
        'Address',
        'IsRepeatBuyer',
    ];

    protected $casts = [
        'IsRepeatBuyer' => 'boolean',
    ];

    /**
     * `CustomerCode` is required and unique, so every new customer gets one
     * automatically (e.g. CUST-7K2M9Q) unless a code is passed in.
     */
    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (empty($customer->CustomerCode)) {
                do {
                    $code = 'CUST-' . strtoupper(Str::random(6));
                } while (static::where('CustomerCode', $code)->exists());

                $customer->CustomerCode = $code;
            }
        });
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'CustomerID', 'CustomerID');
    }

    /**
     * Decides which customer a POS sale belongs to. Called from the sale
     * controller so every transaction is tracked on the Customers page:
     *  - a customer picked from the POS dropdown  -> that customer
     *  - a typed name                             -> found by name, or created
     *                                                (tracked until they qualify)
     *  - an empty or auto-generated "Walk-in ..." -> not tracked (null)
     */
    public static function resolveForSale($customerId, $name): ?int
    {
        if (! empty($customerId)) {
            return static::whereKey($customerId)->value('CustomerID');
        }

        $name = trim((string) $name);

        if ($name === '' || preg_match('/^walk-in\b/i', $name)) {
            return null;
        }

        return static::firstOrCreate(['CustomerName' => $name])->CustomerID;
    }
}