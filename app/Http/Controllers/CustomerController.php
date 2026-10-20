<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Purchases after which a tracked customer qualifies as a repeat buyer.
     * Change it here only: the POS and the Customers page read it from the API.
     */
    private const REPEAT_MIN_PURCHASES = 10;

    /**
     * GET /api/customers?search=juan
     * Feeds the POS name box.
     *  - no search text -> repeat buyers only (the dropdown shown on focus)
     *  - with search    -> every tracked customer that matches, repeat buyers first
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $customers = Customer::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('CustomerName', 'like', "%{$search}%")
                      ->orWhere('ContactNumber', 'like', "%{$search}%");
                });
            })
            ->withCount('sales as visits')
            ->get()
            ->map(fn ($c) => $this->withStatus($c));

        if ($search === '') {
            $customers = $customers->filter(fn ($c) => $c->is_repeat);
        }

        return response()->json(
            $customers
                ->sortBy([['is_repeat', 'desc'], ['visits', 'desc'], ['CustomerName', 'asc']])
                ->values()
                ->take(10)
        );
    }

    /**
     * GET /api/customers/list
     * Every tracked customer with purchase stats. Powers the Customers page.
     */
    public function list()
    {
        $rows = DB::table('customers')
            ->leftJoin('sales', 'sales.CustomerID', '=', 'customers.CustomerID')
            ->groupBy(
                'customers.CustomerID',
                'customers.CustomerCode',
                'customers.CustomerName',
                'customers.ContactNumber',
                'customers.IsRepeatBuyer'
            )
            ->selectRaw('
                customers.CustomerID,
                customers.CustomerCode,
                customers.CustomerName,
                customers.ContactNumber,
                customers.IsRepeatBuyer,
                COUNT(sales.SaleID) as visits,
                COALESCE(SUM(sales.TotalAmount), 0) as total_spent,
                MIN(sales.SaleDate) as first_purchase,
                MAX(sales.SaleDate) as last_purchase
            ')
            ->get()
            ->map(fn ($r) => $this->withStatus($r))
            ->sortBy([['visits', 'desc'], ['CustomerName', 'asc']])
            ->values();

        return response()->json([
            'min_visits' => self::REPEAT_MIN_PURCHASES,
            'customers'  => $rows,
        ]);
    }

    /**
     * POST /api/customers
     * "Add repeat buyer": for buyers already on the client's own list. They
     * qualify immediately, without needing 10 purchases. If someone with the
     * same name is already being tracked, that customer is upgraded instead
     * of creating a duplicate.
     */
    public function store(Request $request)
    {
        $name     = trim((string) $request->input('CustomerName'));
        $existing = $name !== '' ? Customer::where('CustomerName', $name)->first() : null;

        $data = $this->validated($request, $existing?->CustomerID);

        if ($existing) {
            $existing->IsRepeatBuyer = true;
            if (! empty($data['ContactNumber'])) {
                $existing->ContactNumber = $data['ContactNumber'];
            }
            $existing->save();

            return response()->json($existing);
        }

        // CustomerCode is generated automatically by the Customer model.
        // IsRepeatBuyer is assigned directly (not mass-assigned) so it is saved
        // even if 'IsRepeatBuyer' is missing from the model's $fillable list.
        $customer = new Customer($data);
        $customer->IsRepeatBuyer = true;
        $customer->save();

        return response()->json($customer, 201);
    }

    /** PUT /api/customers/{id}: edit name / phone (phone is optional). */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update($this->validated($request, (int) $customer->CustomerID));

        return response()->json($customer);
    }

    /**
     * DELETE /api/customers/{id}
     * Past sales are kept; they become untracked sales again
     * (sales.CustomerID is set to null by the foreign key).
     */
    public function destroy($id)
    {
        Customer::findOrFail($id)->delete();

        return response()->json(['message' => 'Customer deleted.']);
    }

    /** GET /api/customers/{id}: one customer with their full purchase history. */
    public function show($id)
    {
        $customer = Customer::findOrFail($id);

        $sales = DB::table('sales')
            ->where('CustomerID', $customer->CustomerID)
            ->orderByDesc('SaleDate')
            ->get(['SaleID', 'SaleDate', 'TotalAmount', 'DiscountAmount', 'PaymentMethod']);

        $itemsTable = (new SalesItem)->getTable();

        $itemsBySale = DB::table($itemsTable)
            ->join('products', "{$itemsTable}.ProductID", '=', 'products.ProductID')
            ->whereIn("{$itemsTable}.SaleID", $sales->pluck('SaleID'))
            ->select(
                "{$itemsTable}.SaleID",
                'products.ProductName',
                'products.Unit',
                "{$itemsTable}.Quantity",
                "{$itemsTable}.PriceAtSale"
            )
            ->get()
            ->groupBy('SaleID');

        $sales = $sales->map(function ($sale) use ($itemsBySale) {
            $sale->items = $itemsBySale->get($sale->SaleID, collect())->values();
            return $sale;
        });

        return response()->json([
            'customer' => $customer,
            'sales'    => $sales,
        ]);
    }

    /* ── helpers ───────────────────────────────────────────── */

    /**
     * A customer is a repeat buyer when the client listed them (IsRepeatBuyer)
     * OR they have reached REPEAT_MIN_PURCHASES recorded purchases.
     * Works for both Eloquent models and plain query rows.
     */
    private function withStatus($row)
    {
        $listed = (bool) ($row->IsRepeatBuyer ?? false);
        $visits = (int) ($row->visits ?? 0);
        $reached = $visits >= self::REPEAT_MIN_PURCHASES;

        $row->visits       = $visits;
        $row->is_repeat    = $listed || $reached;
        $row->qualified_by = $listed ? 'list' : ($reached ? 'purchases' : null);
        $row->repeat_min   = self::REPEAT_MIN_PURCHASES;

        return $row;
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        // Keep digits only so "0917-123 4567" and "09171234567" match as one number.
        $phone = preg_replace('/\D+/', '', (string) $request->input('ContactNumber'));

        $request->merge([
            'CustomerName'  => trim((string) $request->input('CustomerName')),
            'ContactNumber' => $phone !== '' ? $phone : null,
        ]);

        return $request->validate([
            'CustomerName'  => ['required', 'string', 'max:255'],
            'ContactNumber' => [
                'nullable', 'string', 'min:7', 'max:15',
                Rule::unique('customers', 'ContactNumber')->ignore($ignoreId, 'CustomerID'),
            ],
            'Address'       => ['nullable', 'string', 'max:255'],
        ], [
            'ContactNumber.unique' => 'That phone number already belongs to another customer.',
            'ContactNumber.min'    => 'Phone number looks too short.',
        ]);
    }
}