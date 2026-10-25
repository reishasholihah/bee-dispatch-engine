<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Driver
 */
class DriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status->value,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'location_updated_at' => $this->location_updated_at?->toIso8601String(),
        ];
    }
}
