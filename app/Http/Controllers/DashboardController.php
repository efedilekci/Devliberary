<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Resource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $linkFilter = $request->string('links')->toString();
        $search = mb_substr(trim($request->string('q')->toString()), 0, 200);

        if (! in_array($linkFilter, ['all', 'favorites', 'archived'], true)) {
            $linkFilter = 'active';
        }

        $resources = $user->resources()->latest();

        if ($linkFilter === 'favorites') {
            $resources->where('is_archived', false)->where('is_favorite', true);
        } elseif ($linkFilter === 'archived') {
            $resources->where('is_archived', true);
        } else {
            $resources->where('is_archived', false);
        }

        $resources->search($search);

        $collections = $user->collections()
            ->withCount(['resources' => fn ($query) => $query->where('resources.is_archived', false)])
            ->with(['resources' => function ($query): void {
                $query->where('resources.is_archived', false)
                    ->orderByPivot('position')
                    ->orderBy('resources.title');
            }])
            ->orderByDesc('is_pinned_to_home')
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->map(function (Collection $collection) {
                return [
                    'id' => $collection->id,
                    'name' => $collection->name,
                    'description' => $collection->description,
                    'accent_color' => $collection->accent_color,
                    'resources_count' => $collection->resources_count,
                    'is_pinned_to_home' => $collection->is_pinned_to_home,
                    'is_collapsed' => $collection->is_collapsed,
                    'links' => $collection->resources->take(8)->map(fn (Resource $resource) => [
                        'id' => $resource->id,
                        'title' => $resource->title,
                        'url' => $resource->url,
                        'favicon_url' => $resource->favicon_url,
                        'is_favorite' => $resource->is_favorite,
                    ])->values(),
                ];
            })
            ->values();

        return Inertia::render('dashboard', [
            'collections' => $collections,
            'uncollected' => $user->resources()
                ->where('is_archived', false)
                ->whereDoesntHave('collections')
                ->latest()
                ->limit(30)
                ->get(['id', 'title', 'url', 'favicon_url', 'is_favorite']),
            'linkFilter' => $linkFilter,
            'search' => $search,
            'resources' => $resources
                ->limit(30)
                ->get(['id', 'title', 'url', 'favicon_url', 'is_favorite', 'is_archived', 'created_at']),
        ]);
    }
}
