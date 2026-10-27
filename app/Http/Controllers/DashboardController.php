<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $linkFilter = $request->string('links')->toString();

        if (! in_array($linkFilter, ['favorites', 'archived'], true)) {
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

        return Inertia::render('dashboard', [
            'collections' => $user->collections()
                ->withCount(['resources' => fn ($query) => $query->where('resources.is_archived', false)])
                ->orderByDesc('is_pinned_to_home')
                ->orderBy('position')
                ->orderBy('name')
                ->get(),
            'linkFilter' => $linkFilter,
            'resources' => $resources
                ->limit(30)
                ->get(['id', 'title', 'url', 'favicon_url', 'is_favorite', 'is_archived', 'created_at']),
        ]);
    }
}
