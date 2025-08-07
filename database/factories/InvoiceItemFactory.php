<?php

namespace Database\Factories;

use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $qty = $this->faker->numberBetween(1, 10);
        $price = $this->faker->randomFloat(2, 10, 1000);
        $subtotal = $qty * $price;

        return [
            'item_name' => $this->faker->sentence(3),
            'qty' => $qty,
            'price' => $price,
            'subtotal' => $subtotal,
        ];
    }
}