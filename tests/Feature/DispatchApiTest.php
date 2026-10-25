<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DriverStatus;
use App\Enums\OrderStatus;
use App\Models\Driver;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchApiTest extends TestCase
{
    use RefreshDatabase;

    private const PICKUP_LAT = -6.2000;

    private const PICKUP_LNG = 106.8000;

    private function pendingOrder(): Order
    {
        return Order::factory()->pickupAt(self::PICKUP_LAT, self::PICKUP_LNG)->create();
    }

    private function dispatchUrl(Order|int $order): string
    {
        $id = $order instanceof Order ? $order->id : $order;

        return "/api/v1/orders/{$id}/dispatch";
    }

    public function test_it_dispatches_the_nearest_available_driver(): void
    {
        $order = $this->pendingOrder();
        $near = Driver::factory()->available()->at(-6.2010, 106.8010)->create();
        Driver::factory()->available()->at(-6.2300, 106.8300)->create();

        $response = $this->postJson($this->dispatchUrl($order));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'distance_km',
                    'assigned_at',
                    'order' => [
                        'id',
                        'customer_name',
                        'status',
                        'pickup' => ['latitude', 'longitude'],
                        'dropoff' => ['latitude', 'longitude'],
                    ],
                    'driver' => [
                        'id',
                        'name',
                        'status',
                        'latitude',
                        'longitude',
                        'location_updated_at',
                    ],
                ],
            ])
            ->assertJsonPath('data.driver.id', $near->id)
            ->assertJsonPath('data.driver.status', 'busy')
            ->assertJsonPath('data.order.id', $order->id)
            ->assertJsonPath('data.order.status', 'assigned');

        $this->assertDatabaseHas('assignments', [
            'order_id' => $order->id,
            'driver_id' => $near->id,
        ]);
        $this->assertSame(DriverStatus::Busy, $near->fresh()->status);
        $this->assertSame(OrderStatus::Assigned, $order->fresh()->status);
    }

    public function test_it_returns_404_when_no_drivers_are_available(): void
    {
        $order = $this->pendingOrder();
        Driver::factory()->busy()->at(-6.2010, 106.8010)->create();
        Driver::factory()->at(-6.2020, 106.8020)->create(); // offline

        $this->postJson($this->dispatchUrl($order))
            ->assertNotFound()
            ->assertExactJson(['message' => 'No available drivers within range.']);

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertDatabaseCount('assignments', 0);
    }

    public function test_it_returns_404_when_the_only_driver_is_out_of_range(): void
    {
        $order = $this->pendingOrder();
        // Roughly 55 km away, outside the default 10 km radius.
        $driver = Driver::factory()->available()->at(-5.7000, 106.8000)->create();

        $this->postJson($this->dispatchUrl($order))->assertNotFound();

        $this->assertSame(DriverStatus::Available, $driver->fresh()->status);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_it_returns_404_for_an_unknown_order(): void
    {
        $this->postJson($this->dispatchUrl(999999))->assertNotFound();
    }

    public function test_it_returns_409_when_the_order_is_already_dispatched(): void
    {
        $order = $this->pendingOrder();
        Driver::factory()->available()->count(2)->create([
            'latitude' => -6.2010,
            'longitude' => 106.8010,
        ]);

        $this->postJson($this->dispatchUrl($order))->assertOk();

        $this->postJson($this->dispatchUrl($order))
            ->assertStatus(409)
            ->assertJsonStructure(['message']);

        $this->assertDatabaseCount('assignments', 1);
        $this->assertSame(1, Driver::query()->where('status', DriverStatus::Busy)->count());
    }

    public function test_a_busy_driver_is_not_reused_for_a_second_order(): void
    {
        $first = $this->pendingOrder();
        $second = $this->pendingOrder();
        Driver::factory()->available()->at(-6.2010, 106.8010)->create();

        $this->postJson($this->dispatchUrl($first))->assertOk();
        $this->postJson($this->dispatchUrl($second))->assertNotFound();

        $this->assertSame(OrderStatus::Pending, $second->fresh()->status);
        $this->assertDatabaseCount('assignments', 1);
    }

    public function test_the_dispatch_route_is_registered(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('orders.dispatch'));
    }
}
