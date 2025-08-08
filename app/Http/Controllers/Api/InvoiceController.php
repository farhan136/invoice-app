<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


/**
 * @OA\Tag(
 * name="Invoices",
 * description="API Endpoints for invoice management"
 * )
 * @OA\Schema(
 * schema="Invoice",
 * title="Invoice",
 * required={"due_date", "total"},
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="user_id", type="integer", example=1),
 * @OA\Property(property="customer_id", type="integer", example=1),
 * @OA\Property(property="invoice_number", type="string", example="INV-20230807-0001"),
 * @OA\Property(property="due_date", type="string", format="date", example="2023-09-07"),
 * @OA\Property(property="total", type="number", format="float", example=175000),
 * @OA\Property(property="created_at", type="string", format="date-time", example="2023-08-07T12:00:00Z"),
 * @OA\Property(property="updated_at", type="string", format="date-time", example="2023-08-07T12:00:00Z"),
 * @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/InvoiceItem")),
 * @OA\Property(property="customer", ref="#/components/schemas/CustomerResource") 
 * )
 * @OA\Schema(
 * schema="InvoiceItem",
 * title="InvoiceItem",
 * required={"item_name", "qty", "price"},
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="invoice_id", type="integer", example=1),
 * @OA\Property(property="item_name", type="string", example="Service 1"),
 * @OA\Property(property="qty", type="integer", example=2),
 * @OA\Property(property="price", type="number", format="float", example=50000),
 * @OA\Property(property="subtotal", type="number", format="float", example=100000)
 * )
 */
class InvoiceController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $invoices = Invoice::where('user_id', $request->user()->id)
            // Eager load customer data along with items
            ->with(['items', 'customer'])
            ->paginate(10);

        return response()->json($invoices);
    }

    /**
     * @OA\Post(
     * path="/api/invoices",
     * tags={"Invoices"},
     * summary="Create a new invoice",
     * security={{"sanctum":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"customer_id", "due_date", "items"},
     * @OA\Property(property="customer_id", type="integer", example=1),
     * @OA\Property(property="due_date", type="string", format="date", example="2023-09-07"),
     * @OA\Property(property="items", type="array",
     * @OA\Items(
     * required={"item_name", "qty", "price"},
     * @OA\Property(property="item_name", type="string", example="Service 1"),
     * @OA\Property(property="qty", type="integer", example=2),
     * @OA\Property(property="price", type="number", format="float", example=50000)
     * )
     * )
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Invoice created successfully",
     * @OA\JsonContent(ref="#/components/schemas/Invoice")
     * )
     * )
     */
    public function store(Request $request)
    {
        // Authorize that the user can create an invoice.
        $this->authorize('create', Invoice::class);

        $validated = $request->validate([
            // Add customer_id validation. It's required and must exist in the customers table.
            'customer_id' => 'required|exists:customers,id',
            'due_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $validated['user_id'] = $request->user()->id;

        $invoice = Invoice::createWithItems($validated);

        // Load relations for the response
        return response()->json($invoice->load(['items', 'customer']), 201);
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        // Eager load customer data for the response
        return response()->json($invoice->load(['items', 'customer']));
    }

    /**
     * @OA\Put(
     * path="/api/invoices/{invoice}",
     * tags={"Invoices"},
     * summary="Update an existing invoice",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="invoice",
     * in="path",
     * required=true,
     * @OA\Schema(type="integer"),
     * description="Invoice ID"
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"customer_id", "due_date", "items"},
     * @OA\Property(property="customer_id", type="integer", example=1),
     * @OA\Property(property="due_date", type="string", format="date", example="2023-10-07"),
     * @OA\Property(property="items", type="array",
     * @OA\Items(
     * required={"item_name", "qty", "price"},
     * @OA\Property(property="item_name", type="string", example="Updated Item"),
     * @OA\Property(property="qty", type="integer", example=3),
     * @OA\Property(property="price", type="number", format="float", example=100000)
     * )
     * )
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Invoice updated successfully",
     * @OA\JsonContent(ref="#/components/schemas/Invoice")
     * )
     * )
     */
    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            // Add customer_id validation for update as well
            'customer_id' => 'required|exists:customers,id',
            'due_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $invoice->updateWithItems($validated);

        // Return the fresh model with relations loaded
        return response()->json($invoice->fresh()->load(['items', 'customer']));
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted']);
    }
}