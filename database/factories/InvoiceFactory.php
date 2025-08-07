<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'customer_id' => \App\Models\Customer::factory(),
            'due_date' => $this->faker->date(),
            'total' => $this->faker->randomFloat(2, 100, 10000),
        ];
    }
}