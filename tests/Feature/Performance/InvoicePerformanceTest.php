<?php

namespace Tests\Feature\Performance;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoicePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function list_invoices_endpoint_is_performant_under_sequential_load()
    {
        Invoice::factory()
            ->for($this->user)
            ->for($this->customer) // Associate with customer
            ->hasItems(3)
            ->count(50)
            ->create();
        
        $numberOfRequests = 100;
        $durations = [];

        for ($i = 0; $i < $numberOfRequests; $i++) {
            $startTime = microtime(true);
            $this->getJson('/api/invoices');
            $durations[] = (microtime(true) - $startTime) * 1000;
        }
        
        $averageDuration = array_sum($durations) / count($durations);
        $maxDuration = max($durations);
        
        $this->assertLessThan(50, $averageDuration, "Average response time exceeded 50ms. Actual: {$averageDuration}ms");
        $this->assertLessThan(250, $maxDuration, "Maximum response time exceeded 250ms. Actual: {$maxDuration}ms");
    }

    /** @test */
    public function create_invoice_endpoint_is_efficient()
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'items' => [
                ['item_name' => 'Service 1', 'qty' => 2, 'price' => 50000],
                ['item_name' => 'Product 1', 'qty' => 1, 'price' => 75000],
            ]
        ];

        DB::enableQueryLog();
        $response = $this->postJson('/api/invoices', $payload);
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(201);
        $this->assertLessThanOrEqual(10, count($log), 'The number of queries to create an invoice is too high.');
    }
}