<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DriverStatus;
use App\Enums\OrderStatus;
use App\Models\Assignment;
use App\Models\Driver;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_factory_states_set_status(): void
    {
        $this->assertSame(DriverStatus::Offline, Driver::factory()->create()->status);
        $this->assertSame(DriverStatus::Available, Driver::factory()->available()->create()->status);
        $this->assertSame(DriverStatus::Busy, Driver::factory()->busy()->create()->status);
    }

    public function test_available_scope_only_returns_available_drivers(): void
    {
        Driver::factory()->available()->count(2)->create();
        Driver::factory()->busy()->create();
        Driver::factory()->create();

        $this->assertCount(2, Driver::available()->get());
    }

    public function test_pending_scope_only_returns_pending_orders(): void
    {
        Order::factory()->count(3)->create();
        Order::factory()->assigned()->create();

        $this->assertCount(3, Order::pending()->get());
    }

    public function test_assignment_links_order_and_driver(): void
    {
        $order = Order::factory()->create();
        $driver = Driver::factory()->available()->create();

        $assignment = Assignment::create([
            'order_id' => $order->id,
            'driver_id' => $driver->id,
            'distance_km' => 1.234,
            'assigned_at' => now(),
        ]);

        $this->assertTrue($assignment->order->is($order));
        $this->assertTrue($assignment->driver->is($driver));
        $this->assertTrue($order->assignment->is($assignment));
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_an_order_cannot_be_assigned_twice(): void
    {
        $order = Order::factory()->create();
        $drivers = Driver::factory()->available()->count(2)->create();

        Assignment::create([
            'order_id' => $order->id,
            'driver_id' => $drivers[0]->id,
            'distance_km' => 1,
            'assigned_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Assignment::create([
            'order_id' => $order->id,
            'driver_id' => $drivers[1]->id,
            'distance_km' => 2,
            'assigned_at' => now(),
        ]);
    }
}
