<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\LineItem;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::all()->random()->id,
        ];
    }

    /**
     * Indicate that the order should have line items.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withLineItems()
    {
        return $this->has(
            LineItem::factory()
                ->count(2)
                ->state(function (array $attributes, Order $order) {
                    return ['order_id' => $order->id, 'product_id' => Product::all()->random()->id, 'quantity' => $this->faker->numberBetween(1, 3)];
                })
        );
    }
}
