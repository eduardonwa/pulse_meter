<?php

namespace Database\Seeders;

use App\Models\ProductEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use RuntimeException;

class ProductEventFixtureSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Deben coincidir exactamente con la sesión existente
         * en traffic-summary.json.
         */
        $ipAddress = '203.0.113.10';

        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126.0 Safari/537.36';

        /*
         * IDs fijos para poder ejecutar el seeder varias veces
         * sin crear duplicados.
         */
        $visitorId = '22222222-2222-4222-8222-222222222222';
        $sessionId = '33333333-3333-4333-8333-333333333333';

        /*
         * El access log está en UTC:
         *
         * 09/Jul/2026 19:05:00 +0000
         *
         * Los eventos empiezan inmediatamente después de que
         * el navegador terminó de cargar el JavaScript.
         */
        $events = [
            [
                'event_id' => '11111111-1111-4111-8111-111111111101',
                'event_name' => 'app_opened',
                'occurred_at' => '2026-07-10 16:12:05',
                'properties' => [],
            ],
            [
                'event_id' => '11111111-1111-4111-8111-111111111102',
                'event_name' => 'exercise_form_opened',
                'occurred_at' => '2026-07-10 16:12:12',
                'properties' => [
                    'mode' => 'create',
                ],
            ],
            [
                'event_id' => '11111111-1111-4111-8111-111111111103',
                'event_name' => 'exercise_created',
                'occurred_at' => '2026-07-10 16:12:20',
                'properties' => [
                    'exercise_index' => 3,
                    'exercise_origin' => 'custom',
                    'bpm' => 120,
                    'exercise_mode' => 'timer',
                    'duration_seconds' => 60,
                    'exercise_count' => 4,
                ],
            ],
            [
                'event_id' => '11111111-1111-4111-8111-111111111104',
                'event_name' => 'playback_started',
                'occurred_at' => '2026-07-10 16:12:30',
                'properties' => [
                    'source' => 'exercise',
                    'exercise_index' => 3,
                    'exercise_origin' => 'custom',
                    'exercise_mode' => 'timer',
                    'bpm' => 120,
                    'configured_duration_seconds' => 60,
                ],
            ],
            [
                'event_id' => '11111111-1111-4111-8111-111111111105',
                'event_name' => 'playback_stopped',
                'occurred_at' => '2026-07-10 16:12:45',
                'properties' => [
                    'source' => 'exercise',
                    'exercise_index' => 3,
                    'exercise_origin' => 'custom',
                    'exercise_mode' => 'timer',
                    'bpm' => 120,
                    'duration_seconds' => 15,
                    'configured_duration_seconds' => 60,
                    'stop_reason' => 'user',
                    'engaged' => false,
                ],
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

            $event->visitor_id = $visitorId;
            $event->session_id = $sessionId;
            $event->event_name = $data['event_name'];
            $event->stage = $stage;
            $event->properties = $data['properties'];
            $event->path = '/';
            $event->ip_address = $ipAddress;
            $event->user_agent = $userAgent;

            $event->occurred_at = Carbon::parse(
                $data['occurred_at'],
                'UTC'
            );

            $event->save();
        }
    }
}