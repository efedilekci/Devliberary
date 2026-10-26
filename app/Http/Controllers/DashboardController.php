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

        return Inertia::render('dashboard', [
            'collections' => $user->collections()
                ->withCount('resources')
                ->orderByDesc('is_pinned_to_home')
                ->orderBy('position')
                ->orderBy('name')
                ->get(),
            'resources' => $user->resources()
                ->where('is_archived', false)
                ->latest()
                ->limit(30)
                ->get(['id', 'title', 'url', 'favicon_url', 'is_favorite', 'created_at']),
        ]);
    }
}
