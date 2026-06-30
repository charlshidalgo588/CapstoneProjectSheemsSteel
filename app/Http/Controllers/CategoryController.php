<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * ==========================================================
     * 📌 GET ALL CATEGORIES (Vue dropdown + listings)
     * ==========================================================
     * FIX: this query never actually counted related products —
     * it only selected CategoryID/CategoryName, so the frontend's
     * `cat.products_count` was always undefined and silently
     * fell back to 0 for every single category, regardless of
     * how many products were actually assigned to it.
     *
     * withCount('products') adds a real `products_count` attribute
     * to each row via an efficient single subquery (COUNT joined on
     * the products table), instead of N+1 querying per category.
     *
     * RETURNS:
     * {
     *   "categories": [
     *     { "CategoryID": 1, "CategoryName": "Roof", "products_count": 4 }
     *   ]
     * }
     * ==========================================================
     */
    public function index()
    {
        $categories = Category::select('CategoryID', 'CategoryName')
            ->withCount('products')
            ->orderBy('CategoryName')
            ->get();

        return response()->json([
            'categories' => $categories
        ]);
    }

    /**
     * ==========================================================
     * 📌 GET SINGLE CATEGORY (FIXED)
     * ==========================================================
     * Also returns products_count now, for consistency with the
     * list endpoint and in case a future single-category view
     * (e.g. an edit page) wants to display it.
     *
     * RETURNS:
     * {
     *   "category": {
     *     "CategoryID": 1,
     *     "CategoryName": "Roof",
     *     "products_count": 4
     *   }
     * }
     * ==========================================================
     */
    public function show($id)
    {
        $category = Category::withCount('products')->find($id);

        if (!$category) {
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }

        return response()->json([
            'category' => [
                'CategoryID'     => $category->CategoryID,
                'CategoryName'   => $category->CategoryName,
                'products_count' => $category->products_count,
            ]
        ]);
    }

    /**
     * ==========================================================
     * 📌 CREATE CATEGORY
     * ==========================================================
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'CategoryName' => 'required|string|max:255|unique:categories,CategoryName',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = Category::create([
            'CategoryName' => $request->CategoryName
        ]);

        return response()->json([
            'status'   => 'success',
            'category' => $category
        ], 201);
    }

    /**
     * ==========================================================
     * 📌 UPDATE CATEGORY
     * ==========================================================
     */
    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'CategoryName' =>
                "required|string|max:255|unique:categories,CategoryName,$id,CategoryID",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $category->update([
            'CategoryName' => $request->CategoryName
        ]);

        return response()->json([
            'status'   => 'success',
            'category' => $category
        ]);
    }

    /**
     * ==========================================================
     * 📌 DELETE CATEGORY
     * ==========================================================
     */
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }

        $category->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Category deleted successfully.'
        ]);
    }
}