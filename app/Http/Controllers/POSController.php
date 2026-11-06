<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class POSController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        $products = Product::with(['category', 'inventory'])
            ->whereHas('inventory', function ($query) {
                $query->where('QuantityOnHand', '>', 0);
            })
            ->get();

        return view('sales.point-of-sale', compact('categories', 'products'));
    }

    /**
     * GET /api/pos/remnants — available leftover pieces the cashier can sell.
     *
     * suggested_price (the baseline) =
     *   1. BasePrice saved when the cut was sold (list price - cut price), else
     *   2. the proportional share of the list price (older leftovers).
     * The cashier can still edit it in the POS.
     */
    public function remnants()
    {
        if (!Schema::hasTable('product_remnants')) {
            return response()->json([]);
        }

        $vt          = $this->variantTable();
        $hasFull     = Schema::hasColumn('product_remnants', 'FullLength');
        $hasBase     = Schema::hasColumn('product_remnants', 'BasePrice');
        $hasRemLabel = Schema::hasColumn('product_remnants', 'VariantLabel');
        $hasVtLabel  = $vt && Schema::hasColumn($vt, 'Label');
        $hasPrice    = $vt && Schema::hasColumn($vt, 'SellingPrice');

        $q = DB::table('product_remnants as r')
            ->join('products', 'r.ProductID', '=', 'products.ProductID')
            ->where('r.Status', 'available');

        if ($vt) {
            $q->leftJoin($vt, 'r.VariantID', '=', "{$vt}.VariantID");
        }

        if ($hasVtLabel && $hasRemLabel) {
            $labelExpr = "COALESCE(r.VariantLabel, {$vt}.Label) as VariantLabel";
        } elseif ($hasVtLabel) {
            $labelExpr = "{$vt}.Label as VariantLabel";
        } elseif ($hasRemLabel) {
            $labelExpr = 'r.VariantLabel as VariantLabel';
        } else {
            $labelExpr = 'NULL as VariantLabel';
        }

        // Use the variant's price when there is one, otherwise the product's own price.
        $priceExpr = $hasPrice
            ? "COALESCE({$vt}.SellingPrice, products.SellingPrice) as ListPrice"
            : 'products.SellingPrice as ListPrice';

        $rows = $q->select(
            'r.RemnantID', 'r.ProductID', 'r.VariantID', 'r.Quantity', 'r.Length', 'r.Unit',
            'products.ProductName', 'products.SKU', 'products.Product_Image as ImageURL',
            $hasFull ? 'r.FullLength' : DB::raw('NULL as FullLength'),
            $hasBase ? 'r.BasePrice' : DB::raw('NULL as BasePrice'),
            DB::raw($labelExpr),
            DB::raw($priceExpr)
        )->orderBy('products.ProductName')->get();

        return response()->json($rows->map(function ($r) {
            if ((float) $r->BasePrice > 0) {
                // Baseline = list price of the uncut piece minus what the cut was sold for.
                $r->suggested_price = round((float) $r->BasePrice, 2);
            } elseif ((float) $r->FullLength > 0 && (float) $r->ListPrice > 0) {
                // Older leftovers without a saved baseline: same share of the list price.
                $r->suggested_price = round($r->ListPrice * ($r->Length / $r->FullLength), 2);
            } else {
                $r->suggested_price = null;
            }
            return $r;
        })->values());
    }

    /** Name of the sizes table, if the project has one. */
    private function variantTable(): ?string
    {
        return collect(['product_variants', 'variants'])->first(fn ($t) => Schema::hasTable($t));
    }
}