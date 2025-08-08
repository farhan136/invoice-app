<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer; // Add a customer property for reuse

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create(); // Create a customer for all tests
        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function it_can_list_invoices()
    {
        // Associate the invoice with the customer
        Invoice::factory()->for($this->user)->for($this->customer)->count(3)->create();

        $response = $this->getJson('/api/invoices');

        $response->assertOk()
                 ->assertJsonCount(3, 'data')
                 ->assertJsonStructure([
                     'data' => [
                         // Add the customer object to the expected structure
                         '*' => ['id', 'invoice_number', 'customer', 'items']
                     ]
                 ]);
    }

    /** @test */
    public function it_can_create_an_invoice()
    {
        $payload = [
            'customer_id' => $this->customer->id, // Add customer_id to the payload
            'due_date' => now()->addDays(7)->toDateString(),
            'items' => [
                ['item_name' => 'Service 1', 'qty' => 2, 'price' => 50000],
                ['item_name' => 'Product 1', 'qty' => 1, 'price' => 75000],
            ]
        ];

        $response = $this->postJson('/api/invoices', $payload);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'id', 'invoice_number', 'due_date', 'total', 'user_id', 'customer_id', 'items', 'customer'
                 ])
                 ->assertJsonFragment(['total' => 175000])
                 ->assertJsonFragment(['customer_id' => $this->customer->id]); // Assert customer_id is correct

        $this->assertDatabaseHas('invoices', [
            'customer_id' => $this->customer->id,
            'total' => 175000,
        ]);
        $this->assertDatabaseCount('invoice_items', 2);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_an_invoice()
    {
        $response = $this->postJson('/api/invoices', []);

        $response->assertStatus(422)
                 // Add customer_id to the list of expected validation errors
                 ->assertJsonValidationErrors(['due_date', 'items', 'customer_id']);
    }

    /** @test */
    public function it_can_show_an_invoice()
    {
        $invoice = Invoice::factory()->for($this->user)->for($this->customer)->create();

        $response = $this->getJson('/api/invoices/' . $invoice->id);

        $response->assertOk()
                 ->assertJsonFragment(['invoice_number' => $invoice->invoice_number]);
    }
    
    /** @test */
    public function it_can_update_an_invoice()
    {
        $invoice = Invoice::factory()->for($this->user)->for($this->customer)->hasItems(2)->create();

        // Create another customer to test updating the relation
        $newCustomer = Customer::factory()->create();

        $updateData = [
            'customer_id' => $newCustomer->id, // Add customer_id to the update payload
            'due_date' => now()->addDays(10)->toDateString(),
            'items' => [
                ['item_name' => 'Updated Item', 'qty' => 3, 'price' => 100000],
            ]
        ];

        $response = $this->putJson('/api/invoices/' . $invoice->id, $updateData);

        $response->assertOk()
                 ->assertJsonFragment(['due_date' => $updateData['due_date']])
                 ->assertJsonFragment(['total' => 300000]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id, 
            'customer_id' => $newCustomer->id, // Verify the customer_id was updated
            'total' => 300000
        ]);
        $this->assertDatabaseCount('invoice_items', 1);
    }

    /** @test */
    public function it_can_delete_an_invoice()
    {
        $invoice = Invoice::factory()->for($this->user)->for($this->customer)->hasItems(2)->create();

        $response = $this->deleteJson('/api/invoices/' . $invoice->id);

        $response->assertOk()
                 ->assertJson(['message' => 'Invoice deleted']);

        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseCount('invoice_items', 0);
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_invoices()
    {
        $otherUser = User::factory()->create();
        $invoice = Invoice::factory()->for($otherUser)->for($this->customer)->create();

        $this->getJson('/api/invoices/' . $invoice->id)
             ->assertForbidden();

        $this->putJson('/api/invoices/' . $invoice->id, [])
             ->assertForbidden();

        $this->deleteJson('/api/invoices/' . $invoice->id)
             ->assertForbidden();
    }
}