<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResourceRequest;
use App\Support\UrlNormalizer;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ResourceController extends Controller
{
    public function store(StoreResourceRequest $request): RedirectResponse
    {
        $user = $request->user();
        $normalized = UrlNormalizer::normalize($request->string('url')->toString());
        $title = trim($request->string('title')->toString());

        if ($title === '') {
            $host = parse_url($normalized, PHP_URL_HOST);
            $title = is_string($host) && $host !== '' ? $host : $normalized;
        }

        $resource = $user->resources()->create([
            'url' => $request->string('url')->toString(),
            'title' => mb_substr($title, 0, 200),
            'description' => '',
            'notes' => '',
            'tags' => [],
        ]);

        if ($request->filled('collection_id')) {
            $collection = $user->collections()->findOrFail($request->string('collection_id')->toString());
            $collection->attachResource($resource);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Link opgeslagen.']);

        return to_route('dashboard');
    }
}
