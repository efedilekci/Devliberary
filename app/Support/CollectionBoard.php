<?php

namespace App\Support;

use App\Models\Collection;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Support\Collection as SupportCollection;

class CollectionBoard
{
    /**
     * @return array{collections: SupportCollection<int, array<string, mixed>>, uncollected: SupportCollection<int, Resource>}
     */
    public static function forUser(User $user): array
    {
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
                    'icon' => $collection->icon,
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
                        'show_on_home' => $resource->show_on_home,
                        'tags' => $resource->tags,
                    ])->values(),
                ];
            })
            ->values();

        $uncollected = $user->resources()
            ->where('is_archived', false)
            ->whereDoesntHave('collections')
            ->latest()
            ->limit(30)
            ->get(['id', 'title', 'url', 'favicon_url', 'is_favorite', 'show_on_home', 'tags']);

        return [
            'collections' => $collections,
            'uncollected' => $uncollected,
        ];
    }
}
