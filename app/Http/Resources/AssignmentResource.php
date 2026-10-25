<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Assignment
 */
class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'distance_km' => $this->distance_km,
            'assigned_at' => $this->assigned_at->toIso8601String(),
            'order' => OrderResource::make($this->whenLoaded('order')),
            'driver' => DriverResource::make($this->whenLoaded('driver')),
        ];
    }
}
