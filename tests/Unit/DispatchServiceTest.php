<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\DriverStatus;
use App\Enums\OrderStatus;
use App\Models\Assignment;
use App\Models\Driver;
use App\Models\Order;
use App\Services\DispatchService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchServiceTest extends TestCase
{
    use RefreshDatabase;

    private const PICKUP_LAT = -6.2000;

    private const PICKUP_LNG = 106.8000;

    private function pendingOrder(): Order
    {
        return Order::factory()->pickupAt(self::PICKUP_LAT, self::PICKUP_LNG)->create();
    }

    public function test_it_assigns_the_closest_available_driver(): void
    {
        $order = $this->pendingOrder();

        $near = Driver::factory()->available()->at(-6.2010, 106.8010)->create();
        $far = Driver::factory()->available()->at(-6.2500, 106.8500)->create();
        // Closest overall, but busy, so it must be ignored.
        $busy = Driver::factory()->busy()->at(-6.2001, 106.8001)->create();

        $service = new DispatchService(searchRadiusKm: 20.0);
        $assignment = $service->dispatch($order);

        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertTrue($assignment->driver->is($near));
        $this->assertTrue($assignment->order->is($order));
        $this->assertEqualsWithDelta(
            $service->distanceKm(self::PICKUP_LAT, self::PICKUP_LNG, -6.2010, 106.8010),
            $assignment->distance_km,
            0.001,
        );

        $this->assertSame(DriverStatus::Busy, $near->fresh()->status);
        $this->assertSame(DriverStatus::Available, $far->fresh()->status);
        $this->assertSame(DriverStatus::Busy, $busy->fresh()->status);
        $this->assertSame(OrderStatus::Assigned, $order->fresh()->status);
        $this->assertDatabaseCount('assignments', 1);
    }

    public function test_it_returns_null_when_no_drivers_are_available(): void
    {
        $order = $this->pendingOrder();

        Driver::factory()->busy()->at(-6.2010, 106.8010)->create();
        Driver::factory()->at(-6.2020, 106.8020)->create(); // offline

        $assignment = (new DispatchService)->dispatch($order);

        $this->assertNull($assignment);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertDatabaseCount('assignments', 0);
    }

    public function test_it_ignores_drivers_outside_the_bounding_box(): void
    {
        $order = $this->pendingOrder();

        // Roughly 55 km north of the pickup point, far outside a 5 km box.
        $outside = Driver::factory()->available()->at(-5.7000, 106.8000)->create();

        $assignment = (new DispatchService(searchRadiusKm: 5.0))->dispatch($order);

        $this->assertNull($assignment);
        $this->assertSame(DriverStatus::Available, $outside->fresh()->status);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_it_ignores_drivers_in_the_box_corner_but_outside_the_radius(): void
    {
        $order = $this->pendingOrder();

        // Inside the square box for a 10 km radius, but about 13.8 km away.
        Driver::factory()->available()->at(-6.2000 + 0.088, 106.8000 + 0.088)->create();

        $assignment = (new DispatchService(searchRadiusKm: 10.0))->dispatch($order);

        $this->assertNull($assignment);
    }

    public function test_it_breaks_distance_ties_by_lowest_driver_id(): void
    {
        $order = $this->pendingOrder();

        $first = Driver::factory()->available()->at(-6.2010, 106.8010)->create();
        Driver::factory()->available()->at(-6.2010, 106.8010)->create();

        $assignment = (new DispatchService)->dispatch($order);

        $this->assertTrue($assignment->driver->is($first));
    }

    public function test_it_refuses_to_dispatch_an_order_that_is_not_pending(): void
    {
        $order = Order::factory()->assigned()->create();
        Driver::factory()->available()->create();

        $this->expectException(DomainException::class);

        (new DispatchService(searchRadiusKm: 1000.0))->dispatch($order);
    }

    public function test_haversine_matches_known_distances(): void
    {
        $service = new DispatchService;

        // One degree of longitude on the equator is about 111.195 km.
        $this->assertEqualsWithDelta(111.195, $service->distanceKm(0, 0, 0, 1), 0.01);
        $this->assertEqualsWithDelta(0.0, $service->distanceKm(12.34, 56.78, 12.34, 56.78), 0.0001);
    }
}
