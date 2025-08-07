<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Resources\CustomerResource;

/**
 * @OA\Tag(
 * name="Customers",
 * description="API Endpoints for customer management"
 * )
 */
class CustomerController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/customers",
     * tags={"Customers"},
     * summary="Get a list of customers",
     * security={{"sanctum":{}}},
     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(ref="#/components/schemas/CustomerResource")
     * )
     * )
     * )
     */
    public function index()
    {
        return CustomerResource::collection(Customer::paginate(10));
    }

    /**
     * @OA\Post(
     * path="/api/customers",
     * tags={"Customers"},
     * summary="Create a new customer",
     * security={{"sanctum":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name"},
     * @OA\Property(property="name", type="string", example="John Doe"),
     * @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     * @OA\Property(property="phone", type="string", example="1234567890")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Customer created successfully",
     * @OA\JsonContent(ref="#/components/schemas/CustomerResource")
     * )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:customers,email',
            'phone' => 'nullable|string|max:20',
        ]);

        $customer = Customer::create($validated);

        return new CustomerResource($customer);
    }

    /**
     * @OA\Get(
     * path="/api/customers/{customer}",
     * tags={"Customers"},
     * summary="Get a specific customer",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="customer",
     * in="path",
     * required=true,
     * @OA\Schema(type="integer"),
     * description="Customer ID"
     * ),
     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(ref="#/components/schemas/CustomerResource")
     * )
     * )
     */
    public function show(Customer $customer)
    {
        return new CustomerResource($customer);
    }

    /**
     * @OA\Put(
     * path="/api/customers/{customer}",
     * tags={"Customers"},
     * summary="Update an existing customer",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="customer",
     * in="path",
     * required=true,
     * @OA\Schema(type="integer"),
     * description="Customer ID"
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="name", type="string", example="Jane Doe"),
     * @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     * @OA\Property(property="phone", type="string", example="0987654321")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Customer updated successfully",
     * @OA\JsonContent(ref="#/components/schemas/CustomerResource")
     * )
     * )
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $customer->update($validated);

        return new CustomerResource($customer);
    }

    /**
     * @OA\Delete(
     * path="/api/customers/{customer}",
     * tags={"Customers"},
     * summary="Delete a customer",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="customer",
     * in="path",
     * required=true,
     * @OA\Schema(type="integer"),
     * description="Customer ID"
     * ),
     * @OA\Response(
     * response=200,
     * description="Customer deleted successfully",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Customer deleted successfully")
     * )
     * )
     * )
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json(['message' => 'Customer deleted successfully']);
    }
}