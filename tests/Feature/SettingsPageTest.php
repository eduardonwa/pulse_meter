<?php

namespace Tests\Feature;

use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    public function test_settings_page_does_not_boot_the_practice_player(): void
    {
        $this->get('/settings')
            ->assertOk()
            ->assertSee('Click sound profile')
            ->assertDontSee('Loading your exercises')
            ->assertDontSee('routinePlayer(');
    }

    public function test_legacy_settings_query_redirects_to_standalone_page(): void
    {
        $this->get('/?panel=settings')
            ->assertRedirect('/settings');
    }
}
