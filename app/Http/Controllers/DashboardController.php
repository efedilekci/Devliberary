<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Support\CollectionBoard;
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

        if (! in_array($linkFilter, ['all', 'favorites', 'archived', 'recent'], true)) {
            $linkFilter = 'active';
        }

        $resources = $user->resources();

        if ($linkFilter === 'recent') {
            $resources
                ->where('is_archived', false)
                ->whereNotNull('last_opened_at')
                ->orderByDesc('last_opened_at');
        } else {
            $resources->latest();

            if ($linkFilter === 'favorites') {
                $resources->where('is_archived', false)->where('is_favorite', true);
            } elseif ($linkFilter === 'archived') {
                $resources->where('is_archived', true);
            } else {
                $resources->where('is_archived', false);
            }
        }

        $resources->search($search);

        $board = CollectionBoard::forUser($user);

        return Inertia::render('dashboard', [
            'collections' => $board['collections'],
            'uncollected' => $board['uncollected'],
            'linkFilter' => $linkFilter,
            'search' => $search,
            'resources' => $resources
                ->with('collections:id')
                ->limit(30)
                ->get(['id', 'title', 'url', 'description', 'favicon_url', 'preview_image_url', 'is_favorite', 'is_archived', 'show_on_home', 'tags', 'last_opened_at', 'created_at'])
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
                    'opened_at' => $linkFilter === 'recent' ? $resource->last_opened_at?->toIso8601String() : null,
                    'created_at' => $resource->created_at,
                    'collection_id' => $resource->collections->first()?->id,
                    'collection_ids' => $resource->collections->pluck('id')->values(),
                ])
                ->values(),
        ]);
    }
}
