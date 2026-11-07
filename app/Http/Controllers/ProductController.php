<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Notification;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * =========================================================
     * SKU HELPER
     * =========================================================
     * Builds the next SKU for a product name, e.g. "Tiger Steel" → TIG-00001.
     * Used by generateSku() AND by update() when a product has no SKU.
     */
    private function makeSku(?string $name): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $name), 0, 3));
        $prefix = str_pad($prefix, 3, 'X');

        $lastSku = Product::where('SKU', 'LIKE', "{$prefix}-%")
            ->orderBy('SKU', 'desc')
            ->value('SKU');

        $next = $lastSku ? (intval(substr($lastSku, 4)) + 1) : 1;

        return sprintf('%s-%05d', $prefix, $next);
    }

    /**
     * =========================================================
     * VARIANT HELPERS
     * =========================================================
     * ProductCreate.vue sends `Variants` as a JSON string:
     *   [{
     *      VariantID?,            // present when editing an existing size
     *      Label, Specs:[{Type,Value,Unit}],
     *      SellingPrice, CostPrice,
     *      ReorderLevel?,         // per-size reorder level
     *      OpeningStock?,         // stock for a NEW size
     *      StockAdjustment?       // +/- change for an EXISTING size
     *   }, ...]
     * parseVariants() decodes + validates it; syncVariants() writes it.
     */
    private function parseVariants(Request $request): ?array
    {
        if (!$request->has('Variants')) {
            return null; // not sent → leave existing variants untouched
        }

        $raw = $request->input('Variants');
        $list = is_array($raw) ? $raw : json_decode((string) $raw, true);

        if (!is_array($list) || count($list) === 0) {
            throw ValidationException::withMessages([
                'Variants' => 'Add at least one size and price.',
            ]);
        }

        $seen = [];
        $clean = [];
        foreach ($list as $i => $v) {
            $n = $i + 1;

            if (!is_array($v)) {
                throw ValidationException::withMessages(['Variants' => "Size {$n}: invalid data."]);
            }

            $label = trim((string) ($v['Label'] ?? ''));
            $sell  = $v['SellingPrice'] ?? null;
            $cost  = $v['CostPrice'] ?? 0;

            if ($label === '') $label = 'Standard';
            if (!is_numeric($sell) || (float) $sell <= 0) {
                throw ValidationException::withMessages(['Variants' => "Size {$n}: enter a selling price."]);
            }
            if (!is_numeric($cost) || (float) $cost < 0) {
                throw ValidationException::withMessages(['Variants' => "Size {$n}: enter a valid cost price."]);
            }
            if (isset($seen[mb_strtolower($label)])) {
                throw ValidationException::withMessages(['Variants' => "Size {$n} duplicates another size (\"{$label}\")."]);
            }
            $seen[mb_strtolower($label)] = true;

            // ── per-size stock fields ──────────────────────────────
            // ReorderLevel: null means "not sent" → keep the stored value.
            $reorder = (array_key_exists('ReorderLevel', $v) && $v['ReorderLevel'] !== '' && $v['ReorderLevel'] !== null)
                ? $v['ReorderLevel'] : null;
            $opening = (isset($v['OpeningStock']) && $v['OpeningStock'] !== '') ? $v['OpeningStock'] : 0;
            $adjust  = (isset($v['StockAdjustment']) && $v['StockAdjustment'] !== '') ? $v['StockAdjustment'] : 0;

            foreach (['Reorder level' => $reorder, 'Opening stock' => $opening, 'Stock adjustment' => $adjust] as $name => $val) {
                if ($val !== null && filter_var($val, FILTER_VALIDATE_INT) === false) {
                    throw ValidationException::withMessages(['Variants' => "Size {$n}: {$name} must be a whole number."]);
                }
            }
            if (($reorder !== null && (int) $reorder < 0) || (int) $opening < 0) {
                throw ValidationException::withMessages(['Variants' => "Size {$n}: stock and reorder level cannot be negative."]);
            }

            $clean[] = [
                'VariantID'       => $v['VariantID'] ?? null,
                'Label'           => mb_substr($label, 0, 150),
                'Specs'           => array_values($v['Specs'] ?? []),
                'SellingPrice'    => round((float) $sell, 2),
                'CostPrice'       => round((float) $cost, 2),
                'ReorderLevel'    => $reorder === null ? null : (int) $reorder,
                'OpeningStock'    => (int) $opening,
                'StockAdjustment' => (int) $adjust,
            ];
        }

        return $clean;
    }

    /**
     * Replaces the product's sizes with the submitted list, and applies the
     * per-size stock rules:
     *   - NEW size       → OpeningStock becomes its starting stock (+ stock_in log)
     *   - EXISTING size  → StockAdjustment is added (+ stock_in / stock_out log),
     *                      and may not push stock below zero
     *   - REMOVED size   → blocked while it still has stock
     * Sales keep their own VariantLabel snapshot, so history is safe.
     * Must run inside a DB transaction (it uses lockForUpdate).
     */
    private function syncVariants(Product $product, array $variants): void
    {
        $keep = [];

        foreach ($variants as $v) {
            $id      = $v['VariantID'] ?? null;
            $opening = $v['OpeningStock'];
            $adjust  = $v['StockAdjustment'];

            $attrs = [
                'Label'        => $v['Label'],
                'Specs'        => $v['Specs'],
                'SellingPrice' => $v['SellingPrice'],
                'CostPrice'    => $v['CostPrice'],
            ];
            if ($v['ReorderLevel'] !== null) {
                $attrs['ReorderLevel'] = $v['ReorderLevel'];
            }

            // Existing size (belongs to THIS product) → update in place so its
            // VariantID stays stable. Otherwise it's a new size → create it.
            $existing = $id
                ? $product->variants()->where('VariantID', $id)->lockForUpdate()->first()
                : null;

            if ($existing) {
                $existing->update($attrs);

                if ($adjust !== 0) {
                    $newQty = $existing->QuantityOnHand + $adjust;
                    if ($newQty < 0) {
                        throw ValidationException::withMessages([
                            'Variants' => "\"{$existing->Label}\": stock cannot go below 0.",
                        ]);
                    }
                    $existing->QuantityOnHand = $newQty;
                    $existing->save();

                    $this->logStock(
                        $product, $existing,
                        $adjust > 0 ? 'stock_in' : 'stock_out',
                        abs($adjust),
                        'Manual adjustment'
                    );
                }

                $keep[] = $existing->VariantID;
            } else {
                $created = $product->variants()->create($attrs);

                if ($opening > 0) {
                    $created->QuantityOnHand = $opening;
                    $created->save();

                    $this->logStock(
                        $product, $created, 'stock_in', $opening,
                        $product->wasRecentlyCreated ? 'New product added' : 'New size added'
                    );
                }

                $keep[] = $created->VariantID;
            }
        }

        // Sizes the person removed in the form. Never silently destroy stock.
        $removed = $product->variants()->whereNotIn('VariantID', $keep)->get();
        foreach ($removed as $r) {
            if ($r->QuantityOnHand > 0) {
                throw ValidationException::withMessages([
                    'Variants' => "\"{$r->Label}\" still has {$r->QuantityOnHand} in stock. Set it to 0 before removing the size.",
                ]);
            }
        }
        $product->variants()->whereNotIn('VariantID', $keep)->delete();
    }

    /** One inventory_logs row, tagged with the size it belongs to. */
    private function logStock(Product $product, ProductVariant $variant, string $type, int $qty, string $note): void
    {
        DB::table('inventory_logs')->insert([
            'ProductID'    => $product->ProductID,
            'VariantID'    => $variant->VariantID,
            'VariantLabel' => $variant->Label,
            'type'         => $type,
            'quantity'     => $qty,
            'notes'        => $note,
            'created_by'   => auth()->id() ?? null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    /**
     * =========================================================
     * SHARED FORMATTER — used by productList() AND findByBarcode()
     * so a product found via a fresh barcode scan (not yet in the
     * POS's locally-loaded catalog) looks IDENTICAL to one loaded
     * normally — same ImageURL, same Stock shape, same variants.
     * =========================================================
     */
    private function formatProduct(Product $p): array
    {
        return [
            'ProductID'     => $p->ProductID,
            'ProductName'   => $p->ProductName,
            'SKU'           => $p->SKU,
            'Barcode'       => $p->Barcode,
            'SellingPrice'  => (float) $p->SellingPrice,
            'CostPrice'     => (float) $p->CostPrice,
            'Brand'         => $p->Brand,
            'Description'   => $p->Description,

            // SPECIFICATIONS
            'Material'      => $p->Material,
            'ProfileType'   => $p->ProfileType,
            'Color'         => $p->Color,
            'Length'        => $p->Length,
            'LengthUnit'    => $p->LengthUnit,
            'Width'         => $p->Width,
            'WidthUnit'     => $p->WidthUnit,
            'Thickness'     => $p->Thickness,

            // SIZES & PRICES — what the POS dropdown is built from.
            // CostPrice is intentionally NOT exposed here.
            // Stock / ReorderLevel are PER SIZE.
            'variants' => $p->variants->map(fn ($v) => [
                'VariantID'    => $v->VariantID,
                'Label'        => $v->Label,
                'SellingPrice' => (float) $v->SellingPrice,
                'Stock'        => (int) $v->QuantityOnHand,
                'ReorderLevel' => (int) $v->ReorderLevel,
            ])->values(),

            // CATEGORY
            'category' => [
                'CategoryID'   => $p->category->CategoryID ?? null,
                'CategoryName' => $p->category->CategoryName ?? null,
            ],

            // INVENTORY (product total = sum of its sizes)
            'inventory' => [
                'QuantityOnHand' => $p->inventory->QuantityOnHand ?? 0,
                'ReorderLevel'   => $p->inventory->ReorderLevel ?? 0,
            ],

            // FLAT STOCK (product total)
            'Stock' => $p->inventory->QuantityOnHand ?? 0,

            // IMAGE — always a full URL, never a bare storage path
            'Product_Image' => $p->Product_Image,
            'ImageURL' => $p->Product_Image
                ? asset('storage/' . $p->Product_Image)
                : null,

            // EXTRA
            'CategoryID'   => $p->CategoryID,
            'Unit'         => $p->Unit,
            'Weight'       => $p->Weight,
            'WeightUnit'   => $p->WeightUnit,
            'IsReturnable' => (bool) $p->IsReturnable,
            'created_at'   => $p->created_at,
            'updated_at'   => $p->updated_at,
        ];
    }

    /**
     * =========================================================
     * PRODUCT LIST (USED BY POS + PRODUCT LIST PAGE)
     * =========================================================
     */
    public function productList()
    {
        $products = Product::query()
            ->select([
                'ProductID', 'ProductName', 'SKU', 'Barcode',
                'SellingPrice', 'CostPrice', 'Brand', 'Description',
                'Material', 'ProfileType', 'Color',
                'Length', 'LengthUnit', 'Width', 'WidthUnit', 'Thickness',
                'CategoryID', 'Unit', 'Weight', 'WeightUnit',
                'IsReturnable', 'Product_Image',
                'created_at', 'updated_at',
            ])
            ->with([
                'category:CategoryID,CategoryName',
                'inventory:ProductID,QuantityOnHand,ReorderLevel',
                'variants:VariantID,ProductID,Label,SellingPrice,QuantityOnHand,ReorderLevel',
            ])
            ->orderBy('ProductID', 'desc')
            ->get()
            ->map(fn ($p) => $this->formatProduct($p));

        return response()->json($products);
    }

    /**
     * =========================================================
     * PRODUCT INDEX (MANAGEMENT)
     * =========================================================
     * ⚠️ Response is Laravel's paginator format:
     *   { data: [...], current_page, last_page, per_page, total, ... }
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 50), 100);

        $products = Product::query()
            ->select([
                'ProductID', 'ProductName', 'SKU',
                'SellingPrice', 'CostPrice', 'CategoryID',
                'Material', 'ProfileType', 'Color',
                'Length', 'LengthUnit', 'Width', 'WidthUnit',
                'Product_Image',
            ])
            ->with([
                'category:CategoryID,CategoryName',
                'inventory:ProductID,QuantityOnHand',
                'variants:VariantID,ProductID,Label,SellingPrice,CostPrice,QuantityOnHand,ReorderLevel',
            ])
            ->orderBy('ProductID', 'desc')
            ->paginate($perPage)
            ->through(function ($p) {
                return [
                    'ProductID'    => $p->ProductID,
                    'ProductName'  => $p->ProductName,
                    'SKU'          => $p->SKU,
                    'SellingPrice' => (float) $p->SellingPrice,
                    'CostPrice'    => (float) $p->CostPrice,
                    'Stock'        => $p->inventory->QuantityOnHand ?? 0,
                    'CategoryName' => $p->category->CategoryName ?? null,
                    'CategoryID'   => $p->CategoryID,

                    // SIZES & PRICES (management view includes cost + stock)
                    'variants' => $p->variants->map(fn ($v) => [
                        'VariantID'    => $v->VariantID,
                        'Label'        => $v->Label,
                        'SellingPrice' => (float) $v->SellingPrice,
                        'CostPrice'    => (float) $v->CostPrice,
                        'Stock'        => (int) $v->QuantityOnHand,
                        'ReorderLevel' => (int) $v->ReorderLevel,
                    ])->values(),

                    // SPECIFICATIONS
                    'Material'     => $p->Material,
                    'ProfileType'  => $p->ProfileType,
                    'Color'        => $p->Color,
                    'Length'       => $p->Length,
                    'LengthUnit'   => $p->LengthUnit,
                    'Width'        => $p->Width,
                    'WidthUnit'    => $p->WidthUnit,

                    // IMAGE
                    'Product_Image' => $p->Product_Image,
                    'ImageURL' => $p->Product_Image
                        ? asset('storage/' . $p->Product_Image)
                        : null,
                ];
            });

        return response()->json($products);
    }

    /**
     * =========================================================
     * SHOW SINGLE PRODUCT
     * =========================================================
     */
    public function show($id)
    {
        $product = Product::with(['category', 'inventory', 'suppliers', 'variants'])
            ->findOrFail($id);

        return response()->json([
            'ProductID'     => $product->ProductID,
            'ProductName'   => $product->ProductName,
            'SKU'           => $product->SKU,
            'Barcode'       => $product->Barcode,
            'Unit'          => $product->Unit,
            'Brand'         => $product->Brand,
            'Description'   => $product->Description,

            // SPECIFICATIONS
            'Material'      => $product->Material,
            'ProfileType'   => $product->ProfileType,
            'Color'         => $product->Color,

            'Length'        => $product->Length,
            'LengthUnit'    => $product->LengthUnit,
            'Width'         => $product->Width,
            'WidthUnit'     => $product->WidthUnit,
            'Thickness'     => $product->Thickness,

            'Weight'        => $product->Weight,
            'WeightUnit'    => $product->WeightUnit,

            'SellingPrice'  => (float) $product->SellingPrice,
            'CostPrice'     => (float) $product->CostPrice,
            'IsReturnable'  => (bool) $product->IsReturnable,

            // SIZES & PRICES (full detail incl. Specs + stock, for the edit form)
            'variants' => $product->variants->map(fn ($v) => [
                'VariantID'    => $v->VariantID,
                'Label'        => $v->Label,
                'Specs'        => $v->Specs ?? [],
                'SellingPrice' => (float) $v->SellingPrice,
                'CostPrice'    => (float) $v->CostPrice,
                'Stock'        => (int) $v->QuantityOnHand,
                'ReorderLevel' => (int) $v->ReorderLevel,
            ])->values(),

            'Product_Image' => $product->Product_Image,
            'ImageURL' => $product->Product_Image
                ? asset('storage/' . $product->Product_Image)
                : null,

            'category'  => $product->category,
            'inventory' => $product->inventory,
            'suppliers' => $product->suppliers,
        ]);
    }

    /**
     * =========================================================
     * CREATE PRODUCT
     * =========================================================
     */
    public function store(Request $request)
    {
        $request->validate([
            'Barcode' => ['nullable', 'string', 'max:100', 'unique:products,Barcode'],
        ]);

        DB::beginTransaction();

        try {
            // Throws a 422 ValidationException if sizes/prices/stock are bad.
            // Caught separately below so it isn't turned into a 500.
            $variants = $this->parseVariants($request);

            if ($variants === null) {
                throw ValidationException::withMessages([
                    'Variants' => 'Add at least one size and price.',
                ]);
            }

            // Backward-compat: the products table still has its own price
            // columns, so mirror the FIRST size's price into them.
            $firstSell = $variants[0]['SellingPrice'];
            $firstCost = $variants[0]['CostPrice'];

            $product = Product::create([
                'ProductName'   => $request->ProductName,
                'Unit'          => $request->Unit,
                'CategoryID'    => $request->CategoryID,
                // Use the submitted SKU, or auto-generate one if it's empty.
                'SKU'           => $request->filled('SKU') ? $request->SKU : $this->makeSku($request->ProductName),
                'Barcode'       => $request->filled('Barcode') ? $request->Barcode : null,
                'Brand'         => $request->Brand,
                'Description'   => $request->Description,

                // SPECIFICATIONS
                'Material'      => $request->Material,
                'ProfileType'   => $request->ProfileType,
                'Color'         => $request->Color,
                'Length'        => $request->Length,
                'LengthUnit'    => $request->LengthUnit,
                'Width'         => $request->Width,
                'WidthUnit'     => $request->WidthUnit,
                'Thickness'     => $request->Thickness,

                'Weight'        => $request->Weight,
                'WeightUnit'    => $request->WeightUnit,
                'SellingPrice'  => $firstSell,
                'CostPrice'     => $firstCost,
                'IsReturnable'  => $request->boolean('IsReturnable'),

                'Product_Image' => $request->hasFile('Product_Image')
                    ? $request->file('Product_Image')->store('products', 'public')
                    : null,
            ]);

            // SIZES, PRICES + PER-SIZE OPENING STOCK (+ one stock_in log per size)
            $this->syncVariants($product, $variants);

            // INVENTORY = product total (sum of the sizes' stock)
            StockService::syncProductInventory($product->ProductID);
            $totalOpening = (int) collect($variants)->sum('OpeningStock');

            // SUPPLIER
            if ($request->SupplierID) {
                $product->suppliers()->attach($request->SupplierID, [
                    'PurchasePrice' => $request->PurchasePrice ?? 0,
                ]);
            }

            // NOTIFY: new product added
            Notification::create([
                'type'            => 'product_created',
                'title'           => 'New Product Added',
                'description'     => "\"{$product->ProductName}\" was added to inventory"
                    . ($totalOpening > 0 ? " with an opening stock of {$totalOpening} across " . count($variants) . ' size(s).' : '.'),
                'notifiable_type' => Product::class,
                'notifiable_id'   => $product->ProductID,
                'user_id'         => null,
            ]);

            DB::commit();
            return response()->json(['status' => 'success']);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * =========================================================
     * UPDATE PRODUCT
     * =========================================================
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'Barcode' => [
                'nullable', 'string', 'max:100',
                Rule::unique('products', 'Barcode')->ignore($id, 'ProductID'),
            ],
        ]);

        DB::beginTransaction();

        try {
            $product = Product::with('inventory')->findOrFail($id);

            // null = the edit form didn't send Variants → leave sizes alone.
            $variants = $this->parseVariants($request);

            $product->update([
                'ProductName'  => $request->ProductName,
                'Unit'         => $request->Unit,
                'CategoryID'   => $request->CategoryID,
                // SKU is locked in the edit form and never sent. Keep the stored
                // one; if it's empty (e.g. wiped earlier), generate a new one.
                'SKU'          => $product->SKU ?: $this->makeSku($request->ProductName ?? $product->ProductName),
                'Barcode'      => $request->filled('Barcode') ? $request->Barcode : null,
                'Brand'        => $request->Brand,
                'Description'  => $request->Description,

                // SPECIFICATIONS
                'Material'     => $request->Material,
                'ProfileType'  => $request->ProfileType,
                'Color'        => $request->Color,
                // Legacy single-size dimensions: the edit form no longer sends
                // these (sizes live in variants now), so keep what's stored
                // instead of overwriting it with null.
                'Length'       => $request->has('Length')     ? $request->Length     : $product->Length,
                'LengthUnit'   => $request->has('LengthUnit') ? $request->LengthUnit : $product->LengthUnit,
                'Width'        => $request->has('Width')      ? $request->Width      : $product->Width,
                'WidthUnit'    => $request->has('WidthUnit')  ? $request->WidthUnit  : $product->WidthUnit,
                'Thickness'    => $request->has('Thickness')  ? $request->Thickness  : $product->Thickness,

                'Weight'       => $request->has('Weight')     ? $request->Weight     : $product->Weight,
                'WeightUnit'   => $request->has('WeightUnit') ? $request->WeightUnit : $product->WeightUnit,
                'SellingPrice' => $variants[0]['SellingPrice'] ?? $request->SellingPrice,
                'CostPrice'    => $variants[0]['CostPrice']    ?? $request->CostPrice,
                'IsReturnable' => $request->boolean('IsReturnable'),
            ]);

            if ($variants !== null) {
                $this->syncVariants($product, $variants);
            }

            if ($request->hasFile('Product_Image')) {
                if ($product->Product_Image) {
                    Storage::disk('public')->delete($product->Product_Image);
                }

                $product->update([
                    'Product_Image' => $request->file('Product_Image')->store('products', 'public')
                ]);
            }

            // INVENTORY UPDATE
            if ($variants !== null) {
                // Per-size stock path: product total = sum of the sizes.
                StockService::syncProductInventory($product->ProductID);
            } else {
                // Legacy path (an old edit form that sends no sizes). This
                // changes the product total only and bypasses per-size stock.
                $inventory = $product->inventory;

                if (!$inventory) {
                    $inventory = $product->inventory()->create([
                        'QuantityOnHand' => 0,
                        'ReorderLevel'   => 0,
                    ]);
                }

                if ($request->stock_adjustment) {
                    $inventory->QuantityOnHand += $request->stock_adjustment;
                }

                $inventory->ReorderLevel = $request->ReorderLevel;
                $inventory->LastUpdated  = now();
                $inventory->save();
            }

            DB::commit();
            return response()->json(['status' => 'success']);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * =========================================================
     * RESTOCK PRODUCT (one size at a time)
     * =========================================================
     * Body: { quantity, variant_id }
     * variant_id may be omitted only when the product has a single size.
     */
    public function restock(Request $request, $id)
    {
        $request->validate([
            'quantity'   => 'required|integer|min:1',
            'variant_id' => 'nullable|integer',
        ]);

        DB::beginTransaction();

        try {
            $product = Product::findOrFail($id);

            try {
                $variant = StockService::add(
                    (int) $product->ProductID,
                    $request->filled('variant_id') ? (int) $request->variant_id : null,
                    (int) $request->quantity
                );
            } catch (\RuntimeException $e) {
                throw ValidationException::withMessages(['variant_id' => $e->getMessage()]);
            }

            // LOG STOCK IN (tagged with the size)
            $this->logStock($product, $variant, 'stock_in', (int) $request->quantity, 'Manual restock');

            DB::commit();

            return response()->json([
                'status' => 'success',
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * =========================================================
     * DELETE PRODUCT
     * =========================================================
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->Product_Image) {
            Storage::disk('public')->delete($product->Product_Image);
        }

        $product->suppliers()->detach();
        $product->inventory()->delete();
        $product->variants()->delete();
        $product->delete();

        return response()->json(['status' => 'success']);
    }

    /**
     * =========================================================
     * AUTO GENERATE SKU
     * =========================================================
     */
    public function generateSku($name)
    {
        if (!$name || strlen($name) < 2) {
            return response()->json(['sku' => '']);
        }

        return response()->json([
            'sku' => $this->makeSku($name)
        ]);
    }

    /**
     * =========================================================
     * FIND BY BARCODE — used by POS.vue's scanner (USB + camera)
     * AND by ProductCreate.vue's duplicate-barcode check.
     * =========================================================
     */
    public function findByBarcode($barcode)
    {
        $product = Product::with(['category', 'inventory', 'variants'])
            ->where('Barcode', $barcode)
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'product' => $this->formatProduct($product),
        ]);
    }
}