<?php

namespace Database\Seeders;

use App\Models\ProductEvent;
use Illuminate\Database\Seeder;
use RuntimeException;

class ProductEventFixtureSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'ProductEventFixtureSeeder must not run in production.'
            );
        }

        /*
         * Deben coincidir exactamente con la sesión existente
         * en traffic-summary.json.
         */
        $ipAddress = '203.0.113.10';
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126.0 Safari/537.36';
        $baseTime = now()->utc();
        
        /*
         * Dos visitantes, cada uno con una sesión y un evento.
         */
        $events = [
            [
                'event_id' => '11111111-1111-4111-8111-111111111101',
                'visitor_id' => '22222222-2222-4222-8222-222222222221',
                'session_id' => '33333333-3333-4333-8333-333333333331',
                'event_name' => 'app_opened',
                'occurred_at' => $baseTime->copy()->subMinutes(2),
                'properties' => [],
            ],
            [
                'event_id' => '11111111-1111-4111-8111-111111111102',
                'visitor_id' => '22222222-2222-4222-8222-222222222222',
                'session_id' => '33333333-3333-4333-8333-333333333332',
                'event_name' => 'app_opened',
                'occurred_at' => $baseTime->copy()->subMinute(),
                'properties' => [],
            ],
        ];

        foreach ($events as $data) {
            $stage = config(
                "product_analytics.events.{$data['event_name']}"
            );

            if (! is_string($stage)) {
                throw new RuntimeException(
                    "No stage configured for {$data['event_name']}."
                );
            }

            $event = ProductEvent::query()->firstOrNew([
                'event_id' => $data['event_id'],
            ]);

            $event->visitor_id = $data['visitor_id'];
            $event->session_id = $data['session_id'];
            $event->event_name = $data['event_name'];
            $event->stage = $stage;
            $event->properties = $data['properties'];
            $event->path = '/';
            $event->ip_address = $ipAddress;
            $event->user_agent = $userAgent;
            $event->occurred_at = $data['occurred_at'];

            $event->save();
        }
    }
}