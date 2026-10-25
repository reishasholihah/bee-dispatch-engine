<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'pickup_latitude',
        'pickup_longitude',
        'dropoff_latitude',
        'dropoff_longitude',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'pickup_latitude' => 'float',
            'pickup_longitude' => 'float',
            'dropoff_latitude' => 'float',
            'dropoff_longitude' => 'float',
        ];
    }

    public function assignment(): HasOne
    {
        return $this->hasOne(Assignment::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::Pending);
    }
}
