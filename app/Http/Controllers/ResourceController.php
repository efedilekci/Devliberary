<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResourceRequest;
use App\Models\Resource;
use App\Support\LinkMetadata;
use App\Support\LinkMetadataFetcher;
use App\Support\UrlNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Throwable;

class ResourceController extends Controller
{
    public function store(StoreResourceRequest $request): RedirectResponse
    {
        $user = $request->user();
        $normalized = UrlNormalizer::normalize($request->string('url')->toString());
        $title = trim($request->string('title')->toString());
        $metadata = $this->metadata($normalized);

        if ($title === '') {
            $host = parse_url($normalized, PHP_URL_HOST);
            $title = $metadata->title !== ''
                ? $metadata->title
                : (is_string($host) && $host !== '' ? $host : $normalized);
        }

        $resource = $user->resources()->create([
            'url' => $request->string('url')->toString(),
            'title' => mb_substr($title, 0, 200),
            'description' => '',
            'favicon_url' => $metadata->faviconUrl,
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

    public function favorite(Request $request, Resource $resource): RedirectResponse
    {
        abort_unless($resource->user_id === $request->user()->id, 404);

        $resource->update([
            'is_favorite' => ! $resource->is_favorite,
        ]);

        return back();
    }

    public function destroy(Request $request, Resource $resource): RedirectResponse
    {
        abort_unless($resource->user_id === $request->user()->id, 404);

        $resource->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Link verwijderd.']);

        return back();
    }

    private function metadata(string $url): LinkMetadata
    {
        try {
            return app(LinkMetadataFetcher::class)->fetch($url);
        } catch (Throwable) {
            return new LinkMetadata;
        }
    }
}
