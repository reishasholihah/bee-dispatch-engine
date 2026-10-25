<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DriverStatus;
use App\Enums\OrderStatus;
use App\Models\Driver;
use App\Models\Order;
use Illuminate\Database\Seeder;

/**
 * Demo data around Bandar Seri Begawan and Kiulap, Brunei.
 *
 * Coordinates are approximate and fixed (not random) so the README demo
 * and the seeder test behave the same on every run.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->drivers() as $driver) {
            Driver::create($driver + ['location_updated_at' => now()]);
        }

        foreach ($this->orders() as $order) {
            Order::create($order + ['status' => OrderStatus::Pending]);
        }
    }

    /**
     * @return list<array{name: string, status: DriverStatus, latitude: float, longitude: float}>
     */
    private function drivers(): array
    {
        return [
            ['name' => 'Hakim Ibrahim', 'status' => DriverStatus::Available, 'latitude' => 4.8950, 'longitude' => 114.9400], // town centre
            ['name' => 'Nurul Aina', 'status' => DriverStatus::Available, 'latitude' => 4.9031, 'longitude' => 114.9180], // Kiulap
            ['name' => 'Zulkifli Hassan', 'status' => DriverStatus::Busy, 'latitude' => 4.9105, 'longitude' => 114.9120],
            ['name' => 'Siti Marina', 'status' => DriverStatus::Available, 'latitude' => 4.9180, 'longitude' => 114.9050],
            ['name' => 'Amirul Danial', 'status' => DriverStatus::Busy, 'latitude' => 4.9255, 'longitude' => 114.9300],
        ];
    }

    /**
     * The service (BeeFood, BeeMeds, BeeMall) is part of customer_name for now;
     * the orders table has no dedicated column for it.
     *
     * @return list<array{customer_name: string, pickup_latitude: float, pickup_longitude: float, dropoff_latitude: float, dropoff_longitude: float}>
     */
    private function orders(): array
    {
        return [
            [
                'customer_name' => 'Aisyah Rahman (BeeFood)',
                'pickup_latitude' => 4.9040, 'pickup_longitude' => 114.9185,
                'dropoff_latitude' => 4.8990, 'dropoff_longitude' => 114.9350,
            ],
            [
                'customer_name' => 'Daniel Wong (BeeMeds)',
                'pickup_latitude' => 4.8960, 'pickup_longitude' => 114.9395,
                'dropoff_latitude' => 4.9120, 'dropoff_longitude' => 114.9210,
            ],
            [
                'customer_name' => 'Farah Zainal (BeeMall)',
                'pickup_latitude' => 4.9160, 'pickup_longitude' => 114.9070,
                'dropoff_latitude' => 4.9020, 'dropoff_longitude' => 114.9480,
            ],
            [
                'customer_name' => 'Hafiz Osman (BeeFood)',
                'pickup_latitude' => 4.9230, 'pickup_longitude' => 114.9290,
                'dropoff_latitude' => 4.9080, 'dropoff_longitude' => 114.9150,
            ],
            [
                'customer_name' => 'Liyana Abdullah (BeeMeds)',
                'pickup_latitude' => 4.9005, 'pickup_longitude' => 114.9130,
                'dropoff_latitude' => 4.9290, 'dropoff_longitude' => 114.9330,
            ],
        ];
    }
}
