<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DriverStatus;
use App\Enums\OrderStatus;
use App\Models\Driver;
use App\Models\Order;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    private function assertInServiceArea(float $latitude, float $longitude): void
    {
        $this->assertGreaterThanOrEqual(4.89, $latitude);
        $this->assertLessThanOrEqual(4.93, $latitude);
        $this->assertGreaterThanOrEqual(114.90, $longitude);
        $this->assertLessThanOrEqual(114.95, $longitude);
    }

    public function test_it_seeds_five_drivers_with_mixed_statuses(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertCount(5, Driver::all());
        $this->assertGreaterThan(0, Driver::query()->where('status', DriverStatus::Available)->count());
        $this->assertGreaterThan(0, Driver::query()->where('status', DriverStatus::Busy)->count());

        foreach (Driver::all() as $driver) {
            $this->assertInServiceArea($driver->latitude, $driver->longitude);
        }
    }

    public function test_it_seeds_five_pending_orders_for_each_bee_service(): void
    {
        $this->seed(DatabaseSeeder::class);

        $orders = Order::all();

        $this->assertCount(5, $orders);
        $this->assertCount(5, Order::pending()->get());

        foreach (['BeeFood', 'BeeMeds', 'BeeMall'] as $service) {
            $this->assertTrue(
                $orders->contains(fn (Order $order) => str_contains($order->customer_name, $service)),
                "No seeded order for {$service}.",
            );
        }

        foreach ($orders as $order) {
            $this->assertInServiceArea($order->pickup_latitude, $order->pickup_longitude);
            $this->assertInServiceArea($order->dropoff_latitude, $order->dropoff_longitude);
            $this->assertSame(OrderStatus::Pending, $order->status);
        }
    }

    public function test_a_seeded_order_can_be_dispatched_to_an_available_driver(): void
    {
        $this->seed(DatabaseSeeder::class);

        $order = Order::query()->orderBy('id')->firstOrFail();
        $busyIds = Driver::query()->where('status', DriverStatus::Busy)->pluck('id');

        $response = $this->postJson("/api/v1/orders/{$order->id}/dispatch")->assertOk();

        $this->assertFalse($busyIds->contains($response->json('data.driver.id')));
        $response->assertJsonPath('data.driver.status', 'busy')
            ->assertJsonPath('data.order.status', 'assigned');
    }
}
