<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\LocalAvatarProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalAvatarProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_csp_safe_data_uri_avatar(): void
    {
        $user = User::factory()->create([
            'name' => 'Ahmad Ali',
            'color' => '#0891b2',
        ]);

        $url = (new LocalAvatarProvider)->get($user);

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $url);
        $this->assertStringNotContainsString('ui-avatars.com', $url);

        $svg = base64_decode(str_replace('data:image/svg+xml;base64,', '', $url));

        $this->assertStringContainsString('AA', $svg);
        $this->assertStringContainsString('#0891b2', $svg);
    }
}
