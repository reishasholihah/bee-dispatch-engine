<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DriverStatus;
use App\Enums\OrderStatus;
use App\Models\Assignment;
use App\Models\Driver;
use App\Models\Order;
use DomainException;
use Illuminate\Support\Facades\DB;

class DispatchService
{
    public const EARTH_RADIUS_KM = 6371.0;

    public function __construct(
        private readonly float $searchRadiusKm = 10.0,
    ) {}

    /**
     * Assign the nearest available driver (within the search radius) to a pending order.
     *
     * Returns null when no available driver is within range.
     *
     * @throws DomainException when the order is not pending
     */
    public function dispatch(Order $order): ?Assignment
    {
        return DB::transaction(function () use ($order): ?Assignment {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== OrderStatus::Pending) {
                throw new DomainException("Order {$order->id} is not pending.");
            }

            [$driver, $distanceKm] = $this->findNearestDriver(
                $order->pickup_latitude,
                $order->pickup_longitude,
            );

            if ($driver === null) {
                return null;
            }

            $driver->update(['status' => DriverStatus::Busy]);
            $order->update(['status' => OrderStatus::Assigned]);

            return Assignment::create([
                'order_id' => $order->id,
                'driver_id' => $driver->id,
                'distance_km' => round($distanceKm, 3),
                'assigned_at' => now(),
            ]);
        });
    }

    /**
     * Great-circle distance between two points in kilometres (Haversine formula).
     */
    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(min(1.0, sqrt($a)));
    }

    /**
     * @return array{0: ?Driver, 1: float}
     */
    private function findNearestDriver(float $lat, float $lng): array
    {
        [$minLat, $maxLat, $minLng, $maxLng] = $this->boundingBox($lat, $lng);

        // Cheap SQL prefilter: only drivers inside the box get the exact distance check.
        $candidates = Driver::query()
            ->available()
            ->whereBetween('latitude', [$minLat, $maxLat])
            ->when(
                $minLng !== null,
                fn ($query) => $query->whereBetween('longitude', [$minLng, $maxLng]),
            )
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $nearest = null;
        $nearestKm = INF;

        foreach ($candidates as $driver) {
            $km = $this->distanceKm($lat, $lng, $driver->latitude, $driver->longitude);

            // The box is square but the search area is a circle, so re-check the radius.
            // Strict "<" means ties go to the lowest driver id (candidates are ordered by id).
            if ($km <= $this->searchRadiusKm && $km < $nearestKm) {
                $nearest = $driver;
                $nearestKm = $km;
            }
        }

        return [$nearest, $nearestKm];
    }

    /**
     * Bounding box around a point for the configured radius.
     *
     * Longitude bounds are null near the poles, where the box would wrap the globe.
     * Boxes that cross the antimeridian (±180°) are not handled.
     *
     * @return array{0: float, 1: float, 2: ?float, 3: ?float} [minLat, maxLat, minLng, maxLng]
     */
    private function boundingBox(float $lat, float $lng): array
    {
        $latDelta = rad2deg($this->searchRadiusKm / self::EARTH_RADIUS_KM);
        $minLat = max(-90.0, $lat - $latDelta);
        $maxLat = min(90.0, $lat + $latDelta);

        $cosLat = cos(deg2rad($lat));

        if ($cosLat < 1e-6) {
            return [$minLat, $maxLat, null, null];
        }

        $lngDelta = rad2deg($this->searchRadiusKm / (self::EARTH_RADIUS_KM * $cosLat));

        return [$minLat, $maxLat, $lng - $lngDelta, $lng + $lngDelta];
    }
}
