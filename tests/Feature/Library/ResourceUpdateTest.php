<?php

namespace Tests\Feature\Library;

use App\Models\Collection;
use App\Models\Resource;
use App\Models\User;
use App\Support\LinkMetadataFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ResourceUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_rename_a_link_and_move_it_to_another_collection(): void
    {
        $user = User::factory()->create();
        $from = Collection::factory()->for($user)->create(['name' => 'Laravel']);
        $to = Collection::factory()->for($user)->create(['name' => 'PHP']);
        $resource = Resource::factory()->for($user)->create([
            'title' => 'Docs',
            'url' => 'https://laravel.com/docs',
            'favicon_url' => 'https://laravel.com/favicon.ico',
        ]);
        $from->attachResource($resource);

        $this->actingAs($user)
            ->from(route('dashboard', ['links' => 'all']))
            ->patch(route('resources.update', $resource), [
                'url' => 'https://laravel.com/docs',
                'title' => 'Laravel documentatie',
                'collection_id' => $to->id,
            ])
            ->assertRedirect(route('dashboard', ['links' => 'all']))
            ->assertSessionHasNoErrors();

        $resource->refresh();
        $this->assertSame('Laravel documentatie', $resource->title);
        $this->assertSame('https://laravel.com/favicon.ico', $resource->favicon_url);
        $this->assertFalse($from->resources()->whereKey($resource->id)->exists());
        $this->assertTrue($to->resources()->whereKey($resource->id)->exists());

        $this->actingAs($user)
            ->get(route('dashboard', ['links' => 'all']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('resources.0.title', 'Laravel documentatie')
                ->where('resources.0.collection_id', $to->id));
    }

    public function test_a_link_can_sit_in_more_than_one_collection(): void
    {
        $user = User::factory()->create();
        $first = Collection::factory()->for($user)->create(['name' => 'Laravel']);
        $second = Collection::factory()->for($user)->create(['name' => 'PHP']);
        $resource = Resource::factory()->for($user)->create([
            'url' => 'https://laravel.com/docs',
            'title' => 'Docs',
        ]);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->patch(route('resources.update', $resource), [
                'url' => 'https://laravel.com/docs',
                'title' => 'Docs',
                'collection_ids' => [$first->id, $second->id],
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors();

        $this->assertTrue($first->resources()->whereKey($resource->id)->exists());
        $this->assertTrue($second->resources()->whereKey($resource->id)->exists());

        $this->actingAs($user)
            ->patch(route('resources.update', $resource), [
                'url' => 'https://laravel.com/docs',
                'title' => 'Docs',
                'collection_ids' => [$second->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse($first->resources()->whereKey($resource->id)->exists());
        $this->assertTrue($second->resources()->whereKey($resource->id)->exists());
    }

    public function test_clearing_the_collection_removes_the_link_from_it(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->for($user)->create();
        $resource = Resource::factory()->for($user)->create([
            'url' => 'https://www.php.net',
            'title' => 'PHP',
        ]);
        $collection->attachResource($resource);

        $this->actingAs($user)
            ->patch(route('resources.update', $resource), [
                'url' => 'https://www.php.net',
                'title' => 'PHP',
                'collection_id' => '',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertFalse($collection->resources()->whereKey($resource->id)->exists());
    }

    public function test_a_written_description_stays_when_the_address_changes(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response(
                '<meta property="og:description" content="Van de pagina">',
                200,
                ['Content-Type' => 'text/html'],
            ),
        ]);
        $this->app->instance(LinkMetadataFetcher::class, new LinkMetadataFetcher(
            static fn (string $host): array => ['93.184.216.34'],
        ));

        $user = User::factory()->create();
        $resource = Resource::factory()->for($user)->create([
            'url' => 'https://laravel.com/docs',
            'title' => 'Docs',
            'description' => 'Oud',
        ]);

        $this->actingAs($user)
            ->patch(route('resources.update', $resource), [
                'url' => 'https://example.com/nieuw',
                'title' => 'Docs',
                'description' => '  Zelf geschreven  ',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('Zelf geschreven', $resource->refresh()->description);

        $this->actingAs($user)
            ->patch(route('resources.update', $resource), [
                'url' => 'https://example.com/anders',
                'title' => 'Docs',
                'description' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Van de pagina', $resource->refresh()->description);
    }

    public function test_a_duplicate_url_and_a_blank_title_are_rejected(): void
    {
        $user = User::factory()->create();
        $resource = Resource::factory()->for($user)->create([
            'url' => 'https://laravel.com',
            'title' => 'Laravel',
        ]);
        Resource::factory()->for($user)->create(['url' => 'https://php.net']);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->patch(route('resources.update', $resource), [
                'url' => 'https://PHP.net',
                'title' => 'Laravel',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('url');

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->patch(route('resources.update', $resource), [
                'url' => 'https://laravel.com',
                'title' => '   ',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('title');

        $this->assertSame('Laravel', $resource->refresh()->title);
    }

    public function test_another_users_link_or_collection_cannot_be_used(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $resource = Resource::factory()->for($other)->create(['title' => 'Geheim']);
        $collection = Collection::factory()->for($other)->create();
        $own = Resource::factory()->for($user)->create([
            'url' => 'https://example.com',
            'title' => 'Voorbeeld',
        ]);

        $this->actingAs($user)
            ->patch(route('resources.update', $resource), [
                'url' => 'https://example.com/ander',
                'title' => 'Overgenomen',
            ])
            ->assertNotFound();

        $this->assertSame('Geheim', $resource->refresh()->title);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->patch(route('resources.update', $own), [
                'url' => 'https://example.com',
                'title' => 'Voorbeeld',
                'collection_id' => $collection->id,
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('collection_id');

        $this->assertFalse($collection->resources()->whereKey($own->id)->exists());
    }
}
