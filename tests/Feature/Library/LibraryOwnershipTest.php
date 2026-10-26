<?php

namespace Tests\Feature\Library;

use App\Models\Collection;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class LibraryOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_same_url_is_unique_per_user_and_shared_across_accounts(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $first = Resource::factory()->for($owner)->create([
            'url' => 'https://Laravel.com/docs/',
        ]);

        $this->assertSame('https://laravel.com/docs', $first->normalized_url);
        $this->assertNotSame($owner->id, $other->id);

        $this->expectException(QueryException::class);

        Resource::factory()->for($owner)->create([
            'url' => 'https://laravel.com/docs',
        ]);
    }

    public function test_another_account_can_save_the_same_url(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        Resource::factory()->for($owner)->create([
            'url' => 'https://laravel.com/docs',
        ]);

        $copy = Resource::factory()->for($other)->create([
            'url' => 'https://laravel.com/docs/',
        ]);

        $this->assertSame($other->id, $copy->user_id);
        $this->assertSame(2, Resource::query()->count());
    }

    public function test_a_collection_only_accepts_links_from_the_same_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $collection = Collection::factory()->for($owner)->create();
        $resource = Resource::factory()->for($owner)->create();
        $foreign = Resource::factory()->for($other)->create();

        $collection->attachResource($resource, 1);

        $this->assertTrue($collection->resources()->whereKey($resource->id)->exists());

        $this->expectException(InvalidArgumentException::class);

        $collection->attachResource($foreign);
    }

    public function test_deleting_a_user_removes_their_library(): void
    {
        $owner = User::factory()->create();
        $collection = Collection::factory()->for($owner)->create();
        $resource = Resource::factory()->for($owner)->create();
        $collection->attachResource($resource);

        $owner->delete();

        $this->assertSame(0, Resource::query()->count());
        $this->assertSame(0, Collection::query()->count());
        $this->assertDatabaseCount('collection_resource', 0);
    }
}
