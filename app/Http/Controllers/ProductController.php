<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * =========================================================
     * SHARED FORMATTER — used by productList() AND findByBarcode()
     * so a product found via a fresh barcode scan (not yet in the
     * POS's locally-loaded catalog) looks IDENTICAL to one loaded
     * normally — same ImageURL, same Stock shape, etc.
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

            // CATEGORY
            'category' => [
                'CategoryID'   => $p->category->CategoryID ?? null,
                'CategoryName' => $p->category->CategoryName ?? null,
            ],

            // INVENTORY
            'inventory' => [
                'QuantityOnHand' => $p->inventory->QuantityOnHand ?? 0,
                'ReorderLevel'   => $p->inventory->ReorderLevel ?? 0,
            ],

            // FLAT STOCK
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
     * POS needs the full catalog client-side for instant search/scan,
     * so this stays unpaginated — but we now only pull the columns
     * formatProduct() actually uses, on both the product row and its
     * relations, instead of every column on every table. Same output
     * shape as before, just cheaper to fetch and smaller to transfer.
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
     * Was previously Product::with(...)->get() — every column, every
     * row, every time, with no limit. That gets linearly slower as the
     * catalog grows. Now: only the columns this response actually uses,
     * and real DB-level pagination (50/page) instead of shipping the
     * whole table.
     *
     * ⚠️ BREAKING CHANGE: response shape changes from a flat array to
     * Laravel's paginator format:
     *   { data: [...], current_page, last_page, per_page, total, ... }
     * The Vue Product List page needs to read `response.data.data`
     * instead of `response.data`, and can use `current_page` / `last_page`
     * / `total` to drive a pager. Accepts `?page=` and optional
     * `?per_page=` (capped at 100) from the frontend.
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
        $product = Product::with(['category', 'inventory', 'suppliers'])
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
        // Only validating Barcode here — everything else keeps its existing
        // (unvalidated) behavior so this doesn't change any other flow.
        // `nullable` + `filled` handling below prevents duplicate empty
        // strings from tripping the DB's unique index on Barcode.
        $request->validate([
            'Barcode' => ['nullable', 'string', 'max:100', 'unique:products,Barcode'],
        ]);

        DB::beginTransaction();

        try {
            $product = Product::create([
                'ProductName'   => $request->ProductName,
                'Unit'          => $request->Unit,
                'CategoryID'    => $request->CategoryID,
                'SKU'           => $request->SKU,
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
                'SellingPrice'  => $request->SellingPrice,
                'CostPrice'     => $request->CostPrice,
                'IsReturnable'  => $request->boolean('IsReturnable'),

                'Product_Image' => $request->hasFile('Product_Image')
                    ? $request->file('Product_Image')->store('products', 'public')
                    : null,
            ]);

            // INVENTORY
            $product->inventory()->create([
                'QuantityOnHand' => $request->OpeningStock ?? 0,
                'ReorderLevel'   => $request->ReorderLevel,
                'LastUpdated'    => now(),
            ]);

            // LOG NEW PRODUCT / OPENING STOCK
            // FIX: previously nothing was ever written to inventory_logs
            // when a product was created, so brand-new products (and their
            // opening stock) never showed up anywhere on the Inventory Logs
            // page — not in Recent Activity, not in Top Adjusted Products,
            // not counted in the summary KPIs. Logging it here as a
            // 'stock_in' row (opening stock genuinely is stock coming in)
            // means it correctly counts toward Total Stock In / Total
            // Adjustments / Manual Adjustments like any other movement.
            // The distinct notes value ('New product added') is what lets
            // the frontend tell this apart from a regular restock and show
            // a "New" badge instead of a plain stock-in pill.
            DB::table('inventory_logs')->insert([
                'ProductID'  => $product->ProductID,
                'type'       => 'stock_in',
                'quantity'   => $request->OpeningStock ?? 0,
                'notes'      => 'New product added',
                'created_by' => auth()->id() ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // SUPPLIER
            if ($request->SupplierID) {
                $product->suppliers()->attach($request->SupplierID, [
                    'PurchasePrice' => $request->PurchasePrice ?? 0,
                ]);
            }

            // NOTIFY: new product added
            // user_id left null so it's visible to everyone — matches
            // NotificationController@index, which treats a null user_id
            // as a broadcast-to-all notification (whereNull('user_id')
            // orWhere('user_id', $request->user()->id)).
            Notification::create([
                'type'            => 'product_created',
                'title'           => 'New Product Added',
                'description'     => "\"{$product->ProductName}\" was added to inventory"
                    . ($request->OpeningStock ? " with an opening stock of {$request->OpeningStock}." : '.'),
                'notifiable_type' => Product::class,
                'notifiable_id'   => $product->ProductID,
                'user_id'         => null,
            ]);

            DB::commit();
            return response()->json(['status' => 'success']);

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
        // Same Barcode validation as store(), but ignoring this product's
        // own current row so re-saving a product without changing its
        // barcode doesn't falsely flag itself as a duplicate.
        $request->validate([
            'Barcode' => [
                'nullable', 'string', 'max:100',
                Rule::unique('products', 'Barcode')->ignore($id, 'ProductID'),
            ],
        ]);

        DB::beginTransaction();

        try {
            $product = Product::with('inventory')->findOrFail($id);

            $product->update([
                'ProductName'  => $request->ProductName,
                'Unit'         => $request->Unit,
                'CategoryID'   => $request->CategoryID,
                'SKU'          => $request->SKU,
                'Barcode'      => $request->filled('Barcode') ? $request->Barcode : null,
                'Brand'        => $request->Brand,
                'Description'  => $request->Description,

                // SPECIFICATIONS
                'Material'     => $request->Material,
                'ProfileType'  => $request->ProfileType,
                'Color'        => $request->Color,
                'Length'       => $request->Length,
                'LengthUnit'   => $request->LengthUnit,
                'Width'        => $request->Width,
                'WidthUnit'    => $request->WidthUnit,
                'Thickness'    => $request->Thickness,

                'Weight'       => $request->Weight,
                'WeightUnit'   => $request->WeightUnit,
                'SellingPrice' => $request->SellingPrice,
                'CostPrice'    => $request->CostPrice,
                'IsReturnable' => $request->boolean('IsReturnable'),
            ]);

            if ($request->hasFile('Product_Image')) {
                if ($product->Product_Image) {
                    Storage::disk('public')->delete($product->Product_Image);
                }

                $product->update([
                    'Product_Image' => $request->file('Product_Image')->store('products', 'public')
                ]);
            }

            // -----------------------------------------------------------
            // INVENTORY UPDATE (FIXED)
            // -----------------------------------------------------------
            // $product->inventory is resolved through Eloquent's magic
            // __get(), so chaining a compound assignment directly onto it
            // (e.g. $product->inventory->QuantityOnHand += ...) triggers:
            //   "Indirect modification of overloaded property
            //    App\Models\Product::$inventory has no effect"
            //
            // Fix: pull the related Inventory model into a real local
            // variable first, then mutate/save that instead.
            $inventory = $product->inventory;

            // Guard against products that somehow have no inventory row
            // yet (legacy data, failed store(), etc.) instead of throwing
            // "Attempt to read property on null".
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
            // -----------------------------------------------------------

            DB::commit();
            return response()->json(['status' => 'success']);

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
     * RESTOCK PRODUCT
     * =========================================================
     */
    public function restock(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $product = Product::with('inventory')->findOrFail($id);

            // Same fix applied here for consistency — pull into a local
            // variable before mutating, even though this one previously
            // worked (single += without ->save() chained inline).
            $inventory = $product->inventory;

            if (!$inventory) {
                $inventory = $product->inventory()->create([
                    'QuantityOnHand' => 0,
                    'ReorderLevel'   => 0,
                ]);
            }

            $inventory->QuantityOnHand += $request->quantity;
            $inventory->LastUpdated = now();
            $inventory->save();

            // LOG STOCK IN
            DB::table('inventory_logs')->insert([
                'ProductID'  => $product->ProductID,
                'type'       => 'stock_in',
                'quantity'   => $request->quantity,
                'notes'      => 'Manual restock',
                'created_by' => auth()->id() ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
            ]);

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

        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 3));
        $prefix = str_pad($prefix, 3, 'X');

        $lastSku = Product::where('SKU', 'LIKE', "{$prefix}-%")
            ->orderBy('SKU', 'desc')
            ->value('SKU');

        $nextNumber = $lastSku
            ? (intval(substr($lastSku, 4)) + 1)
            : 1;

        return response()->json([
            'sku' => sprintf("%s-%05d", $prefix, $nextNumber)
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
        $product = Product::with(['category', 'inventory'])
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
            // Same shape as productList() — so a product found via a fresh
            // scan (not yet in POS's loaded catalog) renders identically,
            // including a proper full-URL product image.
            'product' => $this->formatProduct($product),
        ]);
    }
}