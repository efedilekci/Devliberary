<?php

namespace Tests\Feature\Library;

use App\Models\Collection;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_save_a_link_and_see_it_on_the_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('dashboard'))->post(route('resources.store'), [
            'url' => 'laravel.com/docs',
            'title' => 'Laravel docs',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('resources', [
            'user_id' => $user->id,
            'title' => 'Laravel docs',
            'normalized_url' => 'https://laravel.com/docs',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Laravel docs');
    }

    public function test_a_duplicate_link_is_rejected(): void
    {
        $user = User::factory()->create();
        Resource::factory()->for($user)->create([
            'url' => 'https://laravel.com/docs',
        ]);

        $response = $this->actingAs($user)->from(route('dashboard'))->post(route('resources.store'), [
            'url' => 'https://laravel.com/docs/',
            'title' => 'Nog een keer',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHasErrors('url');
        $this->assertSame(1, Resource::query()->count());
    }

    public function test_a_user_can_create_a_collection_and_put_a_link_in_it(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('dashboard'))->post(route('collections.store'), [
            'name' => 'Laravel',
        ])->assertRedirect(route('dashboard'));

        $collection = Collection::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)->from(route('dashboard'))->post(route('resources.store'), [
            'url' => 'https://laravel.com',
            'title' => 'Laravel',
            'collection_id' => $collection->id,
        ])->assertRedirect(route('dashboard'));

        $this->assertTrue($collection->resources()->where('title', 'Laravel')->exists());
    }

    public function test_a_new_collection_is_on_home_only_when_chosen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('dashboard'))->post(route('collections.store'), [
            'name' => 'Alleen collecties',
            'show_on_home' => '0',
        ])->assertRedirect(route('dashboard'));

        $this->assertFalse(
            $user->collections()->where('name', 'Alleen collecties')->firstOrFail()->is_pinned_to_home,
        );

        $this->actingAs($user)->from(route('collections.index'))->post(route('collections.store'), [
            'name' => 'Op home',
            'show_on_home' => '1',
        ])->assertRedirect(route('collections.index'));

        $this->assertTrue(
            $user->collections()->where('name', 'Op home')->firstOrFail()->is_pinned_to_home,
        );
    }

    public function test_a_link_is_on_home_only_when_chosen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('dashboard'))->post(route('resources.store'), [
            'url' => 'https://example.com',
            'title' => 'Example',
            'show_on_home' => '0',
        ])->assertRedirect(route('dashboard'));

        $this->assertFalse($user->resources()->where('title', 'Example')->firstOrFail()->show_on_home);

        $this->actingAs($user)->from(route('dashboard'))->post(route('resources.store'), [
            'url' => 'https://laravel.com',
            'title' => 'Laravel',
            'show_on_home' => '1',
        ])->assertRedirect(route('dashboard'));

        $this->assertTrue($user->resources()->where('title', 'Laravel')->firstOrFail()->show_on_home);
    }

    public function test_a_user_cannot_file_a_link_in_someone_elses_collection(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $collection = Collection::factory()->for($other)->create();

        $response = $this->actingAs($user)->from(route('dashboard'))->post(route('resources.store'), [
            'url' => 'https://example.com',
            'title' => 'Example',
            'collection_id' => $collection->id,
        ]);

        $response->assertSessionHasErrors('collection_id');
        $this->assertSame(0, Resource::query()->count());
    }

    public function test_collection_cards_list_their_own_links(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user)->create(['name' => 'Design']);
        $inside = Resource::factory()->for($user)->create(['title' => 'ShadCN']);
        $archived = Resource::factory()->for($user)->create([
            'title' => 'Weg',
            'is_archived' => true,
        ]);
        Resource::factory()->for($user)->create(['title' => 'Los']);
        $collection->attachResource($inside);
        $collection->attachResource($archived);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('collections.0.name', 'Design')
                ->where('collections.0.resources_count', 1)
                ->where('collections.0.links.0.title', 'ShadCN')
                ->where('uncollected.0.title', 'Los'));
    }

    public function test_all_links_lists_every_saved_link(): void
    {
        $user = User::factory()->create();
        Resource::factory()->for($user)->create(['title' => 'Docs']);
        Resource::factory()->for($user)->create([
            'title' => 'Weg',
            'is_archived' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard', ['links' => 'all']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('linkFilter', 'all')
                ->has('resources', 1)
                ->where('resources.0.title', 'Docs'));
    }

    public function test_the_sidebar_lists_links_favorites_and_collections(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $collection = Collection::factory()->for($user)->create(['name' => 'Laravel']);
        Collection::factory()->for($other)->create(['name' => 'Niet van jou']);
        $favorite = Resource::factory()->for($user)->create([
            'title' => 'Docs',
            'is_favorite' => true,
        ]);
        Resource::factory()->for($user)->create([
            'title' => 'Weg',
            'is_archived' => true,
        ]);
        $collection->attachResource($favorite);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('libraryNav.linkCount', 1)
                ->where('libraryNav.favoriteCount', 1)
                ->has('libraryNav.collections', 1)
                ->where('libraryNav.collections.0.name', 'Laravel')
                ->where('libraryNav.collections.0.resources_count', 1));
    }

    public function test_search_matches_title_or_url_and_skips_archived_links(): void
    {
        $user = User::factory()->create();
        Resource::factory()->for($user)->create([
            'title' => 'Laravel docs',
            'url' => 'https://laravel.com/docs',
        ]);
        Resource::factory()->for($user)->create([
            'title' => 'PHP',
            'url' => 'https://www.php.net',
        ]);
        Resource::factory()->for($user)->create([
            'title' => 'Laravel archief',
            'url' => 'https://laravel.com/old',
            'is_archived' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard', ['q' => 'laravel']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('search', 'laravel')
                ->where('linkFilter', 'active')
                ->has('resources', 1)
                ->where('resources.0.title', 'Laravel docs'));

        $this->actingAs($user)
            ->get(route('dashboard', ['q' => 'php.net']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('resources', 1)
                ->where('resources.0.title', 'PHP'));

        $this->actingAs($user)
            ->get(route('dashboard', ['q' => '%']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('search', '%')
                ->has('resources', 0));
    }
}
