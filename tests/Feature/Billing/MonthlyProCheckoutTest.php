<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use App\Services\Billing\StartMonthlyProCheckout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Mockery\MockInterface;
use Tests\TestCase;

class MonthlyProCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function createFreeUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create([
                'plan' => 'free',
        ]);
        
        return $user;
    }

    private function createProUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create([
                'plan' => 'pro',
        ]);
        
        return $user;
    }

    public function test_a_guest_cannot_start_monthly_checkout(): void
    {
        $this->post(
            route('billing.pro.monthly.checkout')
        )->assertRedirect(
            route('login')
        );
    }

    public function test_an_authenticated_user_can_start_monthly_checkout(): void
    {
        $user = $this->createFreeUser();

        $this->mock(
            StartMonthlyProCheckout::class,
            function (MockInterface $mock) use ($user): void {
                $mock
                    ->shouldReceive('start')
                    ->once()
                    ->with($user)
                    ->andReturn(
                        new RedirectResponse('/fake-stripe')
                    );
            }
        );

        $this
            ->actingAs($user)
            ->post(
                route('billing.pro.monthly.checkout')
            )
            ->assertRedirect('/fake-stripe');
    }

    public function test_a_pro_user_cannot_start_another_monthly_checkout(): void
    {
        $user = $this->createProUser();

        $this
            ->actingAs($user)
            ->post(
                route('billing.pro.monthly.checkout')
            )
            ->assertStatus(409);
    }
}