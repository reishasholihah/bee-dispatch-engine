<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentResource;
use App\Models\Order;
use App\Services\DispatchService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DispatchController extends Controller
{
    public function dispatchOrder(Order $order, DispatchService $dispatchService): JsonResponse
    {
        try {
            $assignment = $dispatchService->dispatch($order);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        if ($assignment === null) {
            return response()->json(
                ['message' => 'No available drivers within range.'],
                Response::HTTP_NOT_FOUND,
            );
        }

        // JsonResource answers 201 for a freshly created model, but this endpoint
        // dispatches an existing order, so the status is set explicitly.
        return (new AssignmentResource($assignment->load(['order', 'driver'])))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
}
