<?php

declare(strict_types=1);

use App\Http\Controllers\Api\DispatchController;
use Illuminate\Support\Facades\Route;

// The "api" prefix is added automatically, so this serves POST /api/v1/orders/{order}/dispatch.
Route::prefix('v1')->group(function () {
    Route::post('orders/{order}/dispatch', [DispatchController::class, 'dispatchOrder'])
        ->name('orders.dispatch');
});
