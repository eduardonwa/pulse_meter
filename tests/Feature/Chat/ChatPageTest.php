<?php

namespace Tests\Feature\Chat;

use Tests\TestCase;

class ChatPageTest extends TestCase
{
    public function test_chat_uses_the_browser_preferred_language_without_a_localized_url(): void
    {
        $this->withHeader('Accept-Language', 'es-MX,es;q=0.9,en;q=0.8')
            ->get(route('chat.index'))
            ->assertOk()
            ->assertViewHas('locale', 'es');

        $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->get(route('chat.index'))
            ->assertOk()
            ->assertViewHas('locale', 'en');
    }
}
