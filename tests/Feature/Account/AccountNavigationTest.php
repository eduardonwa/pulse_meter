<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_links_to_billing(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSeeText('Profile')
            ->assertSeeText('Plans & billing')
            ->assertSee(
                'href="'.route('billing.index').'"',
                false
            );
    }

    public function test_billing_page_links_to_profile(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSeeText('Profile')
            ->assertSeeText('Plans & billing')
            ->assertSee(
                'href="'.route('profile.edit').'"',
                false
            );
    }

    public function test_old_profile_url_redirects_to_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertRedirect(route('profile.edit'));
    }

    public function test_old_billing_url_redirects_to_account(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/billing')
            ->assertRedirect(route('billing.index'));
    }
}