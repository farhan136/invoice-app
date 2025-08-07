<?php

namespace Tests\Feature\Performance;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoicePerformanceTest extends TestCase
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
    public function list_invoices_endpoint_is_performant_under_sequential_load()
    {
        // Setup: Create a realistic number of records
        Invoice::factory()
            ->for($this->user)
            ->hasItems(3)
            ->count(50)
            ->create();
        
        // --- SEQUENTIAL LOAD TEST ---
        $numberOfRequests = 100; // The number of times we will hit the endpoint
        $durations = [];       // Array to store each request's duration

        for ($i = 0; $i < $numberOfRequests; $i++) {
            $startTime = microtime(true);
            
            $this->getJson('/api/invoices'); // Hit the endpoint

            $durations[] = (microtime(true) - $startTime) * 1000; // Store duration in ms
        }
        
        // Calculate average and maximum response times
        $averageDuration = array_sum($durations) / count($durations);
        $maxDuration = max($durations);
        
        $this->assertLessThan(
            50, // e.g., assert average is below 50ms
            $averageDuration,
            "Average response time exceeded 50ms. Actual: {$averageDuration}ms"
        );

        $this->assertLessThan(
            250,
            $maxDuration,
            "Maximum response time exceeded 250ms. Actual: {$maxDuration}ms"
        );
        
    }

    // The test for creating an invoice remains the same as it's not a GET request.
    /** @test */
    public function create_invoice_endpoint_is_efficient()
    {
        // Setup: Prepare request payload
        $payload = [
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
        $this->assertLessThanOrEqual(8, count($log), 'The number of queries to create an invoice is too high.');
    }
}