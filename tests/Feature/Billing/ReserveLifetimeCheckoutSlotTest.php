<?php

namespace Tests\Feature\Billing;

use App\Models\LifetimeCheckoutReservation;
use App\Models\User;
use App\Services\Billing\ReserveLifetimeCheckoutSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ReserveLifetimeCheckoutSlotTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reserves_an_available_slot(): void
    {
        config()->set(
            'billing.pro.lifetime_limit',
            2
        );

        $user = User::factory()->create();

        $reservation = app(
            ReserveLifetimeCheckoutSlot::class
        )->reserve($user);

        $this->assertSame(
            $user->getKey(),
            $reservation->user_id
        );

        $this->assertSame(
            1,
            $reservation->slot_number
        );

        $this->assertSame(
            1,
            app(
                ReserveLifetimeCheckoutSlot::class
            )->remaining()
        );
    }

    public function test_it_rejects_checkout_when_sold_out(): void
    {
        config()->set(
            'billing.pro.lifetime_limit',
            1
        );

        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $service = app(
            ReserveLifetimeCheckoutSlot::class
        );

        $service->reserve($firstUser);

        try {
            $service->reserve($secondUser);

            $this->fail(
                'A second Lifetime slot was reserved.'
            );
        } catch (HttpException $exception) {
            $this->assertSame(
                409,
                $exception->getStatusCode()
            );

            $this->assertSame(
                'Founding Lifetime is sold out.',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            1,
            LifetimeCheckoutReservation::query()->count()
        );
    }

    public function test_an_expired_reservation_releases_its_slot(): void
    {
        config()->set(
            'billing.pro.lifetime_limit',
            1
        );

        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        LifetimeCheckoutReservation::query()->create([
            'user_id' => $firstUser->getKey(),
            'slot_number' => 1,
            'token' =>
                '11111111-1111-4111-8111-111111111111',
            'stripe_checkout_session_id' => null,
            'reserved_until' => now()->subMinute(),
            'completed_at' => null,
        ]);

        $reservation = app(
            ReserveLifetimeCheckoutSlot::class
        )->reserve($secondUser);

        $this->assertSame(
            $secondUser->getKey(),
            $reservation->user_id
        );

        $this->assertSame(
            1,
            $reservation->slot_number
        );

        $this->assertDatabaseMissing(
            'lifetime_checkout_reservations',
            [
                'user_id' => $firstUser->getKey(),
            ]
        );
    }

    public function test_a_user_cannot_reserve_twice(): void
    {
        config()->set(
            'billing.pro.lifetime_limit',
            2
        );

        $user = User::factory()->create();

        $service = app(
            ReserveLifetimeCheckoutSlot::class
        );

        $service->reserve($user);

        $this->expectException(
            HttpException::class
        );

        $service->reserve($user);
    }
}