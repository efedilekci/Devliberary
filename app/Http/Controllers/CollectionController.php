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
                ->get(['resources.id', 'resources.title', 'resources.url', 'resources.favicon_url', 'resources.is_favorite']),
        ]);
    }
}
