<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class InvoiceController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $invoices = Invoice::where('user_id', $request->user()->id)
            ->with('items')
            ->paginate(10);

        return response()->json($invoices);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => 'nullable|unique:invoices',
            'due_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $validated['user_id'] = $request->user()->id;

        $invoice = Invoice::createWithItems($validated);

        return response()->json($invoice->load('items'), 201);
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        return response()->json($invoice->load('items'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'due_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $invoice->updateWithItems($validated);

        return response()->json($invoice->fresh()->load('items'));
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted']);
    }
}