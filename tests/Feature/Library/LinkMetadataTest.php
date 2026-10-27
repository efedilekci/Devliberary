<?php

namespace Tests\Feature\Library;

use App\Models\User;
use App\Support\LinkMetadataFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LinkMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_saved_link_keeps_the_page_title_and_favicon(): void
    {
        $this->fakePublicPage('<title>Gids</title><link rel="icon" href="/icon.png"><meta property="og:description" content="Korte uitleg."><meta property="og:image" content="https://example.com/cover.jpg">');
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('dashboard'))->post(route('resources.store'), [
            'url' => 'https://example.com/guide',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('resources', [
            'user_id' => $user->id,
            'title' => 'Gids',
            'description' => 'Korte uitleg.',
            'favicon_url' => 'https://example.com/icon.png',
            'preview_image_url' => 'https://example.com/cover.jpg',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Gids')
            ->assertSee('https://example.com/icon.png');
    }

    public function test_a_chosen_title_is_kept_when_the_page_has_another_title(): void
    {
        $this->fakePublicPage('<title>Paginatitel</title><link rel="shortcut icon" href="https://cdn.example/icon.png">');
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('dashboard'))->post(route('resources.store'), [
            'url' => 'https://example.com/guide',
            'title' => 'Mijn titel',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('resources', [
            'user_id' => $user->id,
            'title' => 'Mijn titel',
            'favicon_url' => 'https://cdn.example/icon.png',
        ]);
    }

    public function test_typing_a_url_returns_the_page_title_and_description(): void
    {
        $this->fakePublicPage('<title>Gids</title><meta property="og:description" content="Korte uitleg."><meta property="og:image" content="https://example.com/cover.jpg">');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('resources.preview'), ['url' => 'https://example.com/guide'])
            ->assertOk()
            ->assertJson([
                'title' => 'Gids',
                'description' => 'Korte uitleg.',
                'preview_image_url' => 'https://example.com/cover.jpg',
            ]);
    }

    public function test_a_private_url_returns_no_page_details(): void
    {
        Http::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('resources.preview'), ['url' => 'http://127.0.0.1/admin'])
            ->assertOk()
            ->assertJson([
                'title' => '',
                'description' => '',
                'favicon_url' => '',
                'preview_image_url' => '',
            ]);

        Http::assertNothingSent();
    }

    public function test_a_chosen_description_is_kept(): void
    {
        $this->fakePublicPage('<title>Gids</title><meta property="og:description" content="Van de pagina.">');
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('dashboard'))->post(route('resources.store'), [
            'url' => 'https://example.com/guide',
            'title' => 'Mijn titel',
            'description' => 'Mijn uitleg',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('resources', [
            'user_id' => $user->id,
            'title' => 'Mijn titel',
            'description' => 'Mijn uitleg',
        ]);
    }

    public function test_a_private_link_is_saved_without_a_request(): void
    {
        Http::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('dashboard'))->post(route('resources.store'), [
            'url' => 'http://127.0.0.1/admin',
            'title' => 'Lokaal',
        ])->assertRedirect(route('dashboard'));

        Http::assertNothingSent();
        $this->assertDatabaseHas('resources', [
            'user_id' => $user->id,
            'title' => 'Lokaal',
            'description' => '',
            'favicon_url' => '',
            'preview_image_url' => '',
        ]);
    }

    private function fakePublicPage(string $html): void
    {
        $this->app->instance(LinkMetadataFetcher::class, new LinkMetadataFetcher(
            static fn (string $host): array => ['93.184.216.34'],
        ));

        Http::fake([
            'https://example.com/*' => Http::response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']),
        ]);
    }
}
