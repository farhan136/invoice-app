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

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function it_can_list_invoices()
    {
        Invoice::factory()->for($this->user)->count(3)->create();

        $response = $this->getJson('/api/invoices');

        $response->assertOk()
                 ->assertJsonCount(3, 'data')
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'invoice_number', 'due_date', 'total', 'user_id', 'created_at', 'updated_at', 'items']
                     ]
                 ]);
    }

    /** @test */
    public function it_can_create_an_invoice()
    {
        $payload = [
            'due_date' => now()->addDays(7)->toDateString(),
            'items' => [
                ['item_name' => 'Service 1', 'qty' => 2, 'price' => 50000],
                ['item_name' => 'Product 1', 'qty' => 1, 'price' => 75000],
            ]
        ];

        $response = $this->postJson('/api/invoices', $payload);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'id', 'invoice_number', 'due_date', 'total', 'user_id', 'items'
                 ])
                 ->assertJsonFragment(['total' => 175000]);

        $this->assertDatabaseHas('invoices', [
            'due_date' => $payload['due_date'],
            'total' => 175000,
        ]);
        $this->assertDatabaseCount('invoice_items', 2);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_an_invoice()
    {
        $response = $this->postJson('/api/invoices', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['due_date', 'items']);
    }

    /** @test */
    public function it_can_show_an_invoice()
    {
        $invoice = Invoice::factory()->for($this->user)->create();

        $response = $this->getJson('/api/invoices/' . $invoice->id);

        $response->assertOk()
                 ->assertJsonFragment(['invoice_number' => $invoice->invoice_number]);
    }

    /** @test */
    public function it_returns_404_if_invoice_not_found()
    {
        $response = $this->getJson('/api/invoices/999');

        $response->assertNotFound();
    }

    /** @test */
    public function it_can_update_an_invoice()
    {
        $invoice = Invoice::factory()->for($this->user)->hasItems(2)->create();

        $updateData = [
            'due_date' => now()->addDays(10)->toDateString(),
            'items' => [
                ['item_name' => 'Updated Item', 'qty' => 3, 'price' => 100000],
            ]
        ];

        $response = $this->putJson('/api/invoices/' . $invoice->id, $updateData);

        $response->assertOk()
                 ->assertJsonFragment(['due_date' => $updateData['due_date']])
                 ->assertJsonFragment(['total' => 300000]);

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'total' => 300000]);
        $this->assertDatabaseCount('invoice_items', 1);
    }

    /** @test */
    public function it_can_delete_an_invoice()
    {
        $invoice = Invoice::factory()->for($this->user)->hasItems(2)->create();

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
        $invoice = Invoice::factory()->for($otherUser)->create();

        $this->getJson('/api/invoices/' . $invoice->id)
             ->assertForbidden();

        $this->putJson('/api/invoices/' . $invoice->id, [])
             ->assertForbidden();

        $this->deleteJson('/api/invoices/' . $invoice->id)
             ->assertForbidden();
    }
}