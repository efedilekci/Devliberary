<?php

namespace Tests\Feature\Library;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceBulkTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_update_and_delete_selected_links(): void
    {
        $user = User::factory()->create();
        $first = Resource::factory()->for($user)->create(['title' => 'Eerste']);
        $second = Resource::factory()->for($user)->create(['title' => 'Tweede']);
        $third = Resource::factory()->for($user)->create(['title' => 'Derde']);

        $this->actingAs($user)
            ->post(route('resources.bulk'), [
                'ids' => [$first->id, $second->id],
                'action' => 'favorite',
            ])
            ->assertRedirect();

        $this->assertTrue($first->refresh()->is_favorite);
        $this->assertTrue($second->refresh()->is_favorite);
        $this->assertFalse($third->refresh()->is_favorite);

        $this->actingAs($user)
            ->post(route('resources.bulk'), [
                'ids' => [$first->id, $second->id],
                'action' => 'archive',
            ])
            ->assertRedirect();

        $this->assertTrue($first->refresh()->is_archived);
        $this->assertTrue($second->refresh()->is_archived);
        $this->assertFalse($third->refresh()->is_archived);

        $this->actingAs($user)
            ->post(route('resources.bulk'), [
                'ids' => [$first->id],
                'action' => 'restore',
            ])
            ->assertRedirect();

        $this->assertFalse($first->refresh()->is_archived);

        $this->actingAs($user)
            ->post(route('resources.bulk'), [
                'ids' => [$second->id],
                'action' => 'unfavorite',
            ])
            ->assertRedirect();

        $this->assertFalse($second->refresh()->is_favorite);

        $this->actingAs($user)
            ->post(route('resources.bulk'), [
                'ids' => [$first->id],
                'action' => 'delete',
            ])
            ->assertRedirect();

        $this->assertModelMissing($first);
        $this->assertModelExists($second);
    }

    public function test_a_foreign_link_blocks_the_whole_action(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $own = Resource::factory()->for($user)->create();
        $foreign = Resource::factory()->for($other)->create();

        $this->actingAs($user)
            ->post(route('resources.bulk'), [
                'ids' => [$own->id, $foreign->id],
                'action' => 'archive',
            ])
            ->assertNotFound();

        $this->assertFalse($own->refresh()->is_archived);
        $this->assertFalse($foreign->refresh()->is_archived);
    }
}
