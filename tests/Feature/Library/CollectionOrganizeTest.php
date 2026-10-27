<?php

namespace Tests\Feature\Library;

use App\Models\Collection;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CollectionOrganizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_pin_collapse_and_reorder_a_collection(): void
    {
        $user = User::factory()->create();
        $first = Collection::factory()->for($user)->create([
            'name' => 'Alfa',
            'position' => 0,
        ]);
        $second = Collection::factory()->for($user)->create([
            'name' => 'Beta',
            'position' => 1,
        ]);

        $this->actingAs($user)
            ->patch(route('collections.move', $first), ['direction' => 'down'])
            ->assertRedirect();

        $this->assertSame(1, $first->refresh()->position);
        $this->assertSame(0, $second->refresh()->position);

        $this->actingAs($user)
            ->patch(route('collections.collapse', $second))
            ->assertRedirect();

        $this->assertTrue($second->refresh()->is_collapsed);

        $this->actingAs($user)
            ->patch(route('collections.pin', $first))
            ->assertRedirect();

        $this->assertFalse($first->refresh()->is_pinned_to_home);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('collections.0.name', 'Beta')
                ->where('collections.0.is_pinned_to_home', true)
                ->where('collections.1.name', 'Alfa')
                ->where('collections.1.is_pinned_to_home', false));
    }

    public function test_moving_the_first_collection_up_keeps_the_order(): void
    {
        $user = User::factory()->create();
        $first = Collection::factory()->for($user)->create([
            'name' => 'Alfa',
            'position' => 0,
        ]);
        Collection::factory()->for($user)->create([
            'name' => 'Beta',
            'position' => 1,
        ]);

        $this->actingAs($user)
            ->patch(route('collections.move', $first), ['direction' => 'up'])
            ->assertRedirect();

        $this->assertSame(0, $first->refresh()->position);
    }

    public function test_the_owner_can_update_the_name_icon_and_color(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user)->create([
            'name' => 'Notities',
            'description' => '',
            'icon' => 'folder',
            'accent_color' => '#F97316',
            'is_pinned_to_home' => true,
            'position' => 0,
        ]);
        Collection::factory()->for($user)->create([
            'name' => 'Los',
            'is_pinned_to_home' => false,
            'position' => 0,
        ]);

        $this->actingAs($user)->from(route('dashboard'))->patch(route('collections.update', $collection), [
            'name' => 'Design',
            'description' => 'Inspiratie',
            'icon' => 'palette',
            'accent_color' => '#A855F7',
            'show_on_home' => '0',
        ])->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();

        $collection->refresh();
        $this->assertSame('Design', $collection->name);
        $this->assertSame('Inspiratie', $collection->description);
        $this->assertSame('palette', $collection->icon);
        $this->assertSame('#A855F7', $collection->accent_color);
        $this->assertFalse($collection->is_pinned_to_home);
        $this->assertSame(1, $collection->position);
    }

    public function test_an_update_rejects_an_unknown_icon_and_color(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user)->create(['name' => 'Notities']);

        $this->actingAs($user)->from(route('dashboard'))->patch(route('collections.update', $collection), [
            'name' => 'Fout',
            'icon' => 'nope',
            'accent_color' => '#000000',
        ])->assertSessionHasErrors(['icon', 'accent_color']);

        $this->assertSame('Notities', $collection->fresh()->name);
    }

    public function test_another_user_cannot_update_a_collection(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $collection = Collection::factory()->for($owner)->create(['name' => 'Notities']);

        $this->actingAs($other)
            ->patch(route('collections.update', $collection), [
                'name' => 'Gestolen',
                'icon' => 'rocket',
                'accent_color' => '#3B82F6',
                'show_on_home' => '0',
            ])
            ->assertNotFound();

        $this->assertSame('Notities', $collection->fresh()->name);
        $this->assertTrue($collection->fresh()->is_pinned_to_home);
    }

    public function test_deleting_a_collection_keeps_the_links(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user)->create(['name' => 'Tijdelijk']);
        $resource = Resource::factory()->for($user)->create(['title' => 'Bewaard']);
        $collection->attachResource($resource);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->delete(route('collections.destroy', $collection))
            ->assertRedirect(route('dashboard'));

        $this->assertModelMissing($collection);
        $this->assertModelExists($resource);
        $this->assertFalse($resource->collections()->exists());
    }

    public function test_deleting_from_the_collection_page_returns_to_the_overview(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user)->create();

        $this->actingAs($user)
            ->from(route('collections.show', $collection))
            ->delete(route('collections.destroy', $collection))
            ->assertRedirect(route('collections.index'));

        $this->assertModelMissing($collection);
    }

    public function test_another_user_cannot_delete_a_collection(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $collection = Collection::factory()->for($owner)->create(['name' => 'Notities']);

        $this->actingAs($other)
            ->delete(route('collections.destroy', $collection))
            ->assertNotFound();

        $this->assertModelExists($collection);
    }

    public function test_another_user_cannot_organize_a_collection(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $collection = Collection::factory()->for($owner)->create(['position' => 0]);

        $this->actingAs($other)
            ->patch(route('collections.pin', $collection))
            ->assertNotFound();

        $this->actingAs($other)
            ->patch(route('collections.collapse', $collection))
            ->assertNotFound();

        $this->actingAs($other)
            ->patch(route('collections.move', $collection), ['direction' => 'down'])
            ->assertNotFound();

        $this->assertTrue($collection->fresh()->is_pinned_to_home);
        $this->assertSame(0, $collection->fresh()->position);
    }

    public function test_the_owner_can_reorder_links_inside_a_collection(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user)->create();
        $first = Resource::factory()->for($user)->create(['title' => 'Eerste']);
        $second = Resource::factory()->for($user)->create(['title' => 'Tweede']);
        $collection->attachResource($first, 0);
        $collection->attachResource($second, 1);

        $this->actingAs($user)
            ->from(route('collections.show', $collection))
            ->patch(route('collections.resources.move', [$collection, $second]), ['direction' => 'up'])
            ->assertRedirect(route('collections.show', $collection));

        $this->assertSame(1, (int) $collection->resources()->whereKey($first->id)->firstOrFail()->pivot->position);
        $this->assertSame(0, (int) $collection->resources()->whereKey($second->id)->firstOrFail()->pivot->position);
    }

    public function test_moving_the_first_link_up_keeps_the_order(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user)->create();
        $first = Resource::factory()->for($user)->create(['title' => 'Eerste']);
        $second = Resource::factory()->for($user)->create(['title' => 'Tweede']);
        $collection->attachResource($first, 0);
        $collection->attachResource($second, 1);

        $this->actingAs($user)
            ->patch(route('collections.resources.move', [$collection, $first]), ['direction' => 'up'])
            ->assertRedirect();

        $this->assertSame(0, (int) $collection->resources()->whereKey($first->id)->firstOrFail()->pivot->position);
        $this->assertSame(1, (int) $collection->resources()->whereKey($second->id)->firstOrFail()->pivot->position);
    }

    public function test_another_user_cannot_reorder_a_link(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $collection = Collection::factory()->for($owner)->create();
        $link = Resource::factory()->for($owner)->create();
        $collection->attachResource($link, 0);

        $this->actingAs($other)
            ->patch(route('collections.resources.move', [$collection, $link]), ['direction' => 'down'])
            ->assertNotFound();

        $this->assertSame(0, (int) $collection->resources()->whereKey($link->id)->firstOrFail()->pivot->position);
    }

    public function test_a_link_outside_the_collection_cannot_be_reordered_there(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user)->create();
        $link = Resource::factory()->for($user)->create();

        $this->actingAs($user)
            ->patch(route('collections.resources.move', [$collection, $link]), ['direction' => 'down'])
            ->assertNotFound();
    }

    public function test_removing_a_link_from_a_collection_keeps_it_in_the_library(): void
    {
        $user = User::factory()->create();
        $laravel = Collection::factory()->for($user)->create(['name' => 'Laravel']);
        $notes = Collection::factory()->for($user)->create(['name' => 'Notities']);
        $link = Resource::factory()->for($user)->create(['title' => 'Laravel docs']);
        $laravel->attachResource($link, 0);
        $notes->attachResource($link, 0);

        $this->actingAs($user)
            ->from(route('collections.show', $laravel))
            ->delete(route('collections.resources.destroy', [$laravel, $link]))
            ->assertRedirect(route('collections.show', $laravel));

        $this->assertModelExists($link);
        $this->assertFalse($laravel->resources()->whereKey($link->id)->exists());
        $this->assertTrue($notes->resources()->whereKey($link->id)->exists());
    }

    public function test_another_user_cannot_remove_a_link_from_a_collection(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $collection = Collection::factory()->for($owner)->create();
        $link = Resource::factory()->for($owner)->create();
        $collection->attachResource($link);

        $this->actingAs($other)
            ->delete(route('collections.resources.destroy', [$collection, $link]))
            ->assertNotFound();

        $this->assertTrue($collection->resources()->whereKey($link->id)->exists());
    }
}
