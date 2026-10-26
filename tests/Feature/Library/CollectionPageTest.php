<?php

namespace Tests\Feature\Library;

use App\Models\Collection;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionPageTest extends TestCase
{
    use RefreshDatabase;

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
}
