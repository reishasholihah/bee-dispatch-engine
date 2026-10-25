<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DriverStatus;
use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    /** @use HasFactory<DriverFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'latitude',
        'longitude',
        'location_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DriverStatus::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'location_updated_at' => 'datetime',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', DriverStatus::Available);
    }
}
