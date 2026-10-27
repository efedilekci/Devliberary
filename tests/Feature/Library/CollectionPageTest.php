<?php

namespace Tests\Feature\Library;

use App\Models\Collection;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CollectionPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_collections_page_shows_the_owners_boards(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Collection::factory()->for($user)->create(['name' => 'Laravel']);
        Collection::factory()->for($other)->create(['name' => 'Geheim']);

        $this->get(route('collections.index'))->assertRedirect(route('login'));

        $this->actingAs($user)
            ->get(route('collections.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('collections/index')
                ->has('collections', 1)
                ->where('collections.0.name', 'Laravel'));
    }

    public function test_the_owner_can_open_a_collection_with_its_links(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user)->create(['name' => 'Laravel']);
        $resource = Resource::factory()->for($user)->create(['title' => 'Documentatie']);
        $collection->attachResource($resource);

        $this->actingAs($user)
            ->get(route('collections.show', $collection))
            ->assertOk()
            ->assertSee('Laravel')
            ->assertSee('Documentatie');
    }

    public function test_a_link_saved_from_a_collection_stays_in_that_collection(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user)->create(['name' => 'Design']);

        $this->actingAs($user)
            ->from(route('collections.show', $collection))
            ->post(route('resources.store'), [
                'url' => 'https://example.com',
                'title' => 'Voorbeeld',
                'collection_id' => $collection->id,
            ])
            ->assertRedirect(route('collections.show', $collection))
            ->assertSessionHasNoErrors();

        $this->assertTrue($collection->resources()->where('title', 'Voorbeeld')->exists());
    }

    public function test_search_on_a_collection_stays_inside_that_collection(): void
    {
        $user = User::factory()->create();
        $laravel = Collection::factory()->for($user)->create(['name' => 'Laravel']);
        $notes = Collection::factory()->for($user)->create(['name' => 'Notities']);
        $docs = Resource::factory()->for($user)->create(['title' => 'Laravel docs']);
        $php = Resource::factory()->for($user)->create(['title' => 'PHP']);
        $archived = Resource::factory()->for($user)->create([
            'title' => 'Laravel archief',
            'is_archived' => true,
        ]);
        $note = Resource::factory()->for($user)->create(['title' => 'Laravel notitie']);
        $laravel->attachResource($docs, 0);
        $laravel->attachResource($php, 1);
        $laravel->attachResource($archived, 2);
        $notes->attachResource($note, 0);

        $this->actingAs($user)
            ->get(route('collections.show', ['collection' => $laravel, 'q' => 'Laravel']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('search', 'Laravel')
                ->has('resources', 1)
                ->where('resources.0.title', 'Laravel docs'));
    }

    public function test_another_user_cannot_open_the_collection(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $collection = Collection::factory()->for($owner)->create();

        $this->actingAs($other)
            ->get(route('collections.show', $collection))
            ->assertNotFound();
    }

    public function test_the_owner_can_favorite_and_delete_a_link(): void
    {
        $user = User::factory()->create();
        $resource = Resource::factory()->for($user)->create();

        $this->actingAs($user)
            ->patch(route('resources.favorite', $resource))
            ->assertRedirect();

        $this->assertTrue($resource->refresh()->is_favorite);

        $this->actingAs($user)
            ->delete(route('resources.destroy', $resource))
            ->assertRedirect();

        $this->assertNull($resource->fresh());
    }

    public function test_another_user_cannot_change_a_link(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $resource = Resource::factory()->for($owner)->create();

        $this->actingAs($other)
            ->patch(route('resources.favorite', $resource))
            ->assertNotFound();

        $this->actingAs($other)
            ->delete(route('resources.destroy', $resource))
            ->assertNotFound();

        $this->assertNotNull($resource->fresh());
    }

    public function test_a_collection_can_be_sorted_without_changing_the_manual_order(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user)->create(['name' => 'Laravel']);
        $php = Resource::factory()->for($user)->create([
            'title' => 'PHP',
            'created_at' => '2026-10-27 10:00:00',
        ]);
        $docs = Resource::factory()->for($user)->create([
            'title' => 'Laravel docs',
            'created_at' => '2026-10-27 12:00:00',
        ]);
        $collection->attachResource($php, 0);
        $collection->attachResource($docs, 1);

        $this->actingAs($user)
            ->get(route('collections.show', $collection))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('sort', 'position')
                ->where('resources.0.title', 'PHP')
                ->where('resources.1.title', 'Laravel docs'));

        $this->actingAs($user)
            ->get(route('collections.show', ['collection' => $collection, 'sort' => 'title']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('sort', 'title')
                ->where('resources.0.title', 'Laravel docs')
                ->where('resources.1.title', 'PHP'));

        $this->assertSame(0, (int) $collection->resources()->whereKey($php->id)->first()->pivot->position);
        $this->assertSame(1, (int) $collection->resources()->whereKey($docs->id)->first()->pivot->position);
    }
}
