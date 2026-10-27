<?php

namespace Tests\Feature\Library;

use App\Models\Collection;
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
}
