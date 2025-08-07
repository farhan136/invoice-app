<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
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
    public function it_can_list_customers()
    {
        Customer::factory()->count(3)->create();

        $response = $this->getJson('/api/customers');

        $response->assertOk()
                 ->assertJsonCount(3);
    }

    /** @test */
    public function it_can_create_a_customer()
    {
        $data = [
            'name' => 'Customer A',
            'email' => 'customer@example.com',
            'phone' => '08123456789',
        ];

        $response = $this->postJson('/api/customers', $data);

        $response->assertCreated()
                 ->assertJsonFragment(['name' => 'Customer A']);

        $this->assertDatabaseHas('customers', ['email' => 'customer@example.com']);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_customer()
    {
        $response = $this->postJson('/api/customers', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_can_show_a_customer()
    {
        $customer = Customer::factory()->create();

        $response = $this->getJson("/api/customers/{$customer->id}");

        $response->assertOk()
                 ->assertJsonFragment(['name' => $customer->name]);
    }

    /** @test */
    public function it_returns_404_if_customer_not_found()
    {
        $response = $this->getJson('/api/customers/999');

        $response->assertNotFound();
    }

    /** @test */
    public function it_can_update_a_customer()
    {
        $customer = Customer::factory()->create();

        $updateData = ['name' => 'Updated Name'];

        $response = $this->putJson("/api/customers/{$customer->id}", $updateData);

        $response->assertOk()
                 ->assertJsonFragment(['name' => 'Updated Name']);

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Updated Name']);
    }

    /** @test */
    public function it_can_delete_a_customer()
    {
        $customer = Customer::factory()->create();

        $response = $this->deleteJson("/api/customers/{$customer->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }
}
