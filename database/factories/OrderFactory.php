<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'customer_name' => fake()->name(),
            'pickup_latitude' => fake()->latitude(-6.35, -6.10),
            'pickup_longitude' => fake()->longitude(106.70, 106.95),
            'dropoff_latitude' => fake()->latitude(-6.35, -6.10),
            'dropoff_longitude' => fake()->longitude(106.70, 106.95),
            'status' => OrderStatus::Pending,
        ];
    }

    public function pickupAt(float $latitude, float $longitude): static
    {
        return $this->state([
            'pickup_latitude' => $latitude,
            'pickup_longitude' => $longitude,
        ]);
    }

    public function assigned(): static
    {
        return $this->state(['status' => OrderStatus::Assigned]);
    }
}
