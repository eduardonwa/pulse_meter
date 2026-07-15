<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductEventRequest;
use App\Models\ProductEvent;
use Illuminate\Http\Response;

class ProductEventController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        StoreProductEventRequest $request
    ): Response {
        $data = $request->validated();

        $events = config('product_analytics.events', []);

        $eventName = $data['event_name'];
        $stage = $events[$eventName] ?? null;

        abort_if(
            $stage === null,
            422,
            'Unsupported product analytics event.'
        );

        ProductEvent::firstOrCreate(
            [
                'event_id' => $data['event_id'],
            ],
            [
                'visitor_id' => $data['visitor_id'],
                'session_id' => $data['session_id'],

                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),

                'event_name' => $eventName,
                'stage' => $stage,
                'properties' => $data['properties'] ?? [],
                'path' => $data['path'] ?? null,
                'occurred_at' => now(),
            ]
        );
        return response()->noContent();
    }
}
