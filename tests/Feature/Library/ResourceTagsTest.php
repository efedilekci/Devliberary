<?php

namespace Tests\Feature\Library;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ResourceTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tags_are_saved_and_shown_on_the_link_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('resources.store'), [
                'url' => 'https://example.com/guide',
                'title' => 'Gids',
                'tags' => ' React, documentatie, react, #php ',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors();

        $this->assertSame(
            ['React', 'documentatie', 'php'],
            $user->resources()->firstOrFail()->tags,
        );

        $this->actingAs($user)
            ->get(route('dashboard', ['links' => 'all']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('resources.0.tags', ['React', 'documentatie', 'php']));
    }

    public function test_too_many_tags_are_rejected_and_existing_tags_can_be_cleared(): void
    {
        $user = User::factory()->create();
        $resource = Resource::factory()->for($user)->create([
            'url' => 'https://example.com',
            'title' => 'Voorbeeld',
            'tags' => ['oud'],
        ]);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->patch(route('resources.update', $resource), [
                'url' => 'https://example.com',
                'title' => 'Voorbeeld',
                'tags' => 'een, twee, drie, vier, vijf, zes, zeven, acht, negen',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('tags');

        $this->assertSame(['oud'], $resource->refresh()->tags);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->patch(route('resources.update', $resource), [
                'url' => 'https://example.com',
                'title' => 'Voorbeeld',
                'tags' => 'nieuw',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors();

        $this->assertSame(['nieuw'], $resource->refresh()->tags);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->patch(route('resources.update', $resource), [
                'url' => 'https://example.com',
                'title' => 'Voorbeeld',
                'tags' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame([], $resource->refresh()->tags);
    }

    public function test_search_matches_a_tag(): void
    {
        $user = User::factory()->create();
        Resource::factory()->for($user)->create([
            'title' => 'Los',
            'url' => 'https://example.com/los',
            'tags' => ['documentatie'],
        ]);
        Resource::factory()->for($user)->create([
            'title' => 'Andere',
            'url' => 'https://example.com/andere',
            'tags' => ['ontwerp'],
        ]);

        $this->actingAs($user)
            ->get(route('dashboard', ['q' => 'documentatie']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('resources', 1)
                ->where('resources.0.title', 'Los'));
    }
}
