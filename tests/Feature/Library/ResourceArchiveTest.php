<?php

namespace Tests\Feature\Library;

use App\Models\Collection;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ResourceArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_archive_and_restore_a_link(): void
    {
        $user = User::factory()->create();
        $resource = Resource::factory()->for($user)->create(['title' => 'Documentatie']);

        $this->actingAs($user)
            ->patch(route('resources.archive', $resource))
            ->assertRedirect();

        $this->assertTrue($resource->refresh()->is_archived);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('linkFilter', 'active')
                ->has('resources', 0));

        $this->actingAs($user)
            ->get(route('dashboard', ['links' => 'archived']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('linkFilter', 'archived')
                ->has('resources', 1)
                ->where('resources.0.title', 'Documentatie'));

        $this->actingAs($user)
            ->patch(route('resources.archive', $resource))
            ->assertRedirect();

        $this->assertFalse($resource->refresh()->is_archived);
    }

    public function test_favorites_only_show_active_favorites(): void
    {
        $user = User::factory()->create();
        Resource::factory()->for($user)->create([
            'title' => 'Favoriet',
            'is_favorite' => true,
        ]);
        Resource::factory()->for($user)->create([
            'title' => 'Oude favoriet',
            'is_favorite' => true,
            'is_archived' => true,
        ]);
        Resource::factory()->for($user)->create(['title' => 'Gewoon']);

        $this->actingAs($user)
            ->get(route('dashboard', ['links' => 'favorites']))
            ->assertOk()
            ->assertSee('Favoriet')
            ->assertDontSee('Oude favoriet')
            ->assertDontSee('Gewoon');
    }

    public function test_an_archived_link_leaves_its_collection(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user)->create(['name' => 'Laravel']);
        $resource = Resource::factory()->for($user)->create(['title' => 'Documentatie']);
        $collection->attachResource($resource);

        $this->actingAs($user)
            ->patch(route('resources.archive', $resource))
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('collections.show', $collection))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('resources', 0));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('collections.0.resources_count', 0)
                ->has('resources', 0));
    }

    public function test_another_user_cannot_archive_a_link(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $resource = Resource::factory()->for($owner)->create();

        $this->actingAs($other)
            ->patch(route('resources.archive', $resource))
            ->assertNotFound();

        $this->assertFalse($resource->fresh()->is_archived);
    }
}
