<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DriverStatus;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'status' => DriverStatus::Offline,
            'latitude' => fake()->latitude(-6.35, -6.10),
            'longitude' => fake()->longitude(106.70, 106.95),
            'location_updated_at' => now(),
        ];
    }

    public function available(): static
    {
        return $this->state(['status' => DriverStatus::Available]);
    }

    public function busy(): static
    {
        return $this->state(['status' => DriverStatus::Busy]);
    }

    public function at(float $latitude, float $longitude): static
    {
        return $this->state([
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }
}
