<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollectionRequest;
use App\Models\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function store(StoreCollectionRequest $request): RedirectResponse
    {
        $user = $request->user();
        $position = (int) $user->collections()->max('position') + 1;

        $user->collections()->create([
            'name' => $request->string('name')->toString(),
            'description' => '',
            'position' => $position,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Collectie aangemaakt.']);

        return to_route('dashboard');
    }

    public function show(Request $request, Collection $collection): Response
    {
        abort_unless($collection->user_id === $request->user()->id, 404);

        return Inertia::render('collections/show', [
            'collection' => $collection->only(['id', 'name', 'description', 'accent_color']),
            'resources' => $collection->resources()
                ->where('resources.is_archived', false)
                ->orderByPivot('position')
                ->get(['resources.id', 'resources.title', 'resources.url', 'resources.favicon_url', 'resources.is_favorite', 'resources.is_archived']),
        ]);
    }

    public function pin(Request $request, Collection $collection): RedirectResponse
    {
        abort_unless($collection->user_id === $request->user()->id, 404);

        $pinned = ! $collection->is_pinned_to_home;
        $max = $request->user()->collections()
            ->where('is_pinned_to_home', $pinned)
            ->max('position');

        $collection->update([
            'is_pinned_to_home' => $pinned,
            'position' => $max === null ? 0 : ((int) $max) + 1,
        ]);

        return back();
    }

    public function collapse(Request $request, Collection $collection): RedirectResponse
    {
        abort_unless($collection->user_id === $request->user()->id, 404);

        $collection->update([
            'is_collapsed' => ! $collection->is_collapsed,
        ]);

        return back();
    }

    public function move(Request $request, Collection $collection): RedirectResponse
    {
        abort_unless($collection->user_id === $request->user()->id, 404);

        $direction = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ])['direction'];

        $group = $request->user()->collections()
            ->where('is_pinned_to_home', $collection->is_pinned_to_home)
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->values();

        $index = $group->search(fn (Collection $item): bool => $item->id === $collection->id);

        if ($index === false) {
            return back();
        }

        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($target < 0 || $target >= $group->count()) {
            return back();
        }

        $reordered = $group->all();
        [$reordered[$index], $reordered[$target]] = [$reordered[$target], $reordered[$index]];

        foreach (array_values($reordered) as $position => $item) {
            $item->update(['position' => $position]);
        }

        return back();
    }
}
