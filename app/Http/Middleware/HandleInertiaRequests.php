<?php

namespace App\Http\Middleware;

use App\Models\Collection;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'libraryNav' => fn () => $this->libraryNav($request),
        ];
    }

    /**
     * @return array{linkCount: int, favoriteCount: int, collections: list<array{id: string, name: string, resources_count: int}>}|null
     */
    private function libraryNav(Request $request): ?array
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        return [
            'linkCount' => $user->resources()->where('is_archived', false)->count(),
            'favoriteCount' => $user->resources()->where('is_archived', false)->where('is_favorite', true)->count(),
            'collections' => $user->collections()
                ->withCount(['resources' => fn ($query) => $query->where('resources.is_archived', false)])
                ->orderByDesc('is_pinned_to_home')
                ->orderBy('position')
                ->orderBy('name')
                ->get()
                ->map(fn (Collection $collection) => [
                    'id' => $collection->id,
                    'name' => $collection->name,
                    'resources_count' => (int) $collection->resources_count,
                ])
                ->values()
                ->all(),
        ];
    }
}
