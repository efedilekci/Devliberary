<?php

namespace Tests\Feature\Library;

use App\Models\Snippet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SnippetTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_save_favorite_and_delete_a_snippet(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('snippets.store'), [
                'title' => 'Hallo',
                'language' => 'php',
                'code' => '<?php',
            ])
            ->assertSessionHasErrors('language');

        $this->actingAs($user)
            ->post(route('snippets.store'), [
                'title' => 'Hallo',
                'language' => 'plaintext',
                'code' => "echo 'hoi';",
            ])
            ->assertRedirect(route('snippets.index'));

        $snippet = $user->snippets()->first();

        $this->assertNotNull($snippet);
        $this->assertSame('text', $snippet->language);
        $this->assertSame("echo 'hoi';", $snippet->code);

        $this->actingAs($user)
            ->get(route('snippets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('snippets', 1)
                ->where('snippets.0.title', 'Hallo')
                ->where('snippets.0.language', 'text')
                ->has('languages', 12));

        $this->actingAs($user)
            ->patch(route('snippets.favorite', $snippet))
            ->assertRedirect();

        $this->assertTrue($snippet->refresh()->is_favorite);

        $other = User::factory()->create();

        $this->actingAs($other)
            ->delete(route('snippets.destroy', $snippet))
            ->assertNotFound();

        $this->assertModelExists($snippet);

        $this->actingAs($user)
            ->delete(route('snippets.destroy', $snippet))
            ->assertRedirect();

        $this->assertModelMissing($snippet);
    }

    public function test_deleting_a_user_removes_snippets(): void
    {
        $user = User::factory()->create();
        $snippet = Snippet::factory()->for($user)->create();

        $user->delete();

        $this->assertModelMissing($snippet);
    }
}
