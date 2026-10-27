<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollectionRequest;
use App\Models\Collection;
use App\Models\Resource;
use App\Support\CollectionAppearance;
use App\Support\CollectionBoard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('collections/index', CollectionBoard::forUser($request->user()));
    }

    public function store(StoreCollectionRequest $request): RedirectResponse
    {
        $user = $request->user();
        $position = (int) $user->collections()->max('position') + 1;

        $user->collections()->create([
            'name' => $request->string('name')->toString(),
            'description' => mb_substr(trim($request->string('description')->toString()), 0, 500),
            'icon' => CollectionAppearance::icon($request->string('icon')->toString()),
            'accent_color' => CollectionAppearance::color($request->string('accent_color')->toString()),
            'position' => $position,
            'is_pinned_to_home' => $request->boolean('show_on_home'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Collectie aangemaakt.']);

        return back();
    }

    public function update(StoreCollectionRequest $request, Collection $collection): RedirectResponse
    {
        abort_unless($collection->user_id === $request->user()->id, 404);

        $pinned = $request->boolean('show_on_home');
        $attributes = [
            'name' => $request->string('name')->toString(),
            'description' => mb_substr(trim($request->string('description')->toString()), 0, 500),
            'icon' => CollectionAppearance::icon($request->string('icon')->toString()),
            'accent_color' => CollectionAppearance::color($request->string('accent_color')->toString()),
            'is_pinned_to_home' => $pinned,
        ];

        if ($pinned !== $collection->is_pinned_to_home) {
            $max = $request->user()->collections()
                ->where('is_pinned_to_home', $pinned)
                ->max('position');

            $attributes['position'] = $max === null ? 0 : ((int) $max) + 1;
        }

        $collection->update($attributes);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Collectie bijgewerkt.']);

        return back();
    }

    public function destroy(Request $request, Collection $collection): RedirectResponse
    {
        abort_unless($collection->user_id === $request->user()->id, 404);

        $onShow = $this->cameFromShow($collection);
        $collection->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Collectie verwijderd.']);

        if ($onShow) {
            return redirect()->route('collections.index');
        }

        return back();
    }

    public function show(Request $request, Collection $collection): Response
    {
        abort_unless($collection->user_id === $request->user()->id, 404);

        $search = mb_substr(trim($request->string('q')->toString()), 0, 200);
        $resources = $collection->resources()
            ->where('resources.is_archived', false)
            ->with('collections:id')
            ->orderByPivot('position')
            ->orderBy('resources.title');
        $resources->search($search);

        return Inertia::render('collections/show', [
            'collection' => $collection->only(['id', 'name', 'description', 'icon', 'accent_color', 'is_pinned_to_home']),
            'search' => $search,
            'resources' => $resources
                ->get(['resources.id', 'resources.title', 'resources.url', 'resources.description', 'resources.favicon_url', 'resources.preview_image_url', 'resources.is_favorite', 'resources.is_archived', 'resources.show_on_home', 'resources.tags'])
                ->map(fn (Resource $resource) => [
                    'id' => $resource->id,
                    'title' => $resource->title,
                    'url' => $resource->url,
                    'description' => $resource->description,
                    'favicon_url' => $resource->favicon_url,
                    'preview_image_url' => $resource->preview_image_url,
                    'tags' => $resource->tags,
                    'is_favorite' => $resource->is_favorite,
                    'is_archived' => $resource->is_archived,
                    'show_on_home' => $resource->show_on_home,
                    'collection_id' => $resource->collections->first()?->id,
                    'collection_ids' => $resource->collections->pluck('id')->values(),
                ])
                ->values(),
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

    public function moveResource(Request $request, Collection $collection, Resource $resource): RedirectResponse
    {
        abort_unless($collection->user_id === $request->user()->id, 404);
        abort_unless($resource->user_id === $request->user()->id, 404);

        $direction = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ])['direction'];

        $links = $collection->resources()
            ->where('resources.is_archived', false)
            ->orderByPivot('position')
            ->orderBy('resources.title')
            ->get()
            ->values();

        $index = $links->search(fn (Resource $item): bool => $item->id === $resource->id);

        if ($index === false) {
            abort(404);
        }

        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($target < 0 || $target >= $links->count()) {
            return back();
        }

        $from = (int) $links[$index]->pivot->position;
        $to = (int) $links[$target]->pivot->position;
        $collection->resources()->updateExistingPivot($links[$index]->id, ['position' => $to]);
        $collection->resources()->updateExistingPivot($links[$target]->id, ['position' => $from]);

        return back();
    }

    public function detachResource(Request $request, Collection $collection, Resource $resource): RedirectResponse
    {
        abort_unless($collection->user_id === $request->user()->id, 404);
        abort_unless($resource->user_id === $request->user()->id, 404);
        abort_unless($collection->resources()->whereKey($resource->id)->exists(), 404);

        $collection->resources()->detach($resource->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Link uit de collectie gehaald.']);

        return back();
    }

    private function cameFromShow(Collection $collection): bool
    {
        $previous = parse_url(url()->previous(), PHP_URL_PATH);
        $show = parse_url(route('collections.show', $collection), PHP_URL_PATH);

        return is_string($previous) && is_string($show) && rtrim($previous, '/') === rtrim($show, '/');
    }
}
