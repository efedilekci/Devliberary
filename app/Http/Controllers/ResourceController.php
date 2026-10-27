<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkResourceRequest;
use App\Http\Requests\StoreResourceRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Models\Resource;
use App\Support\LinkMetadata;
use App\Support\LinkMetadataFetcher;
use App\Support\TagList;
use App\Support\UrlNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            'description' => $metadata->description,
            'favicon_url' => $metadata->faviconUrl,
            'preview_image_url' => $metadata->previewImageUrl,
            'notes' => '',
            'tags' => TagList::parse($request->string('tags')->toString()),
        ]);

        if ($request->filled('collection_id')) {
            $collection = $user->collections()->findOrFail($request->string('collection_id')->toString());
            $collection->attachResource($resource);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Link opgeslagen.']);

        return back();
    }

    public function update(UpdateResourceRequest $request, Resource $resource): RedirectResponse
    {
        $normalized = UrlNormalizer::normalize($request->string('url')->toString());
        $attributes = [
            'url' => $request->string('url')->toString(),
            'title' => mb_substr($request->string('title')->toString(), 0, 200),
            'tags' => TagList::parse($request->string('tags')->toString()),
        ];

        $missingPreview = $resource->description === '' && $resource->preview_image_url === '';

        if ($normalized !== $resource->normalized_url || $missingPreview) {
            $metadata = $this->metadata($normalized);

            if ($normalized !== $resource->normalized_url || $resource->favicon_url === '') {
                $attributes['favicon_url'] = $metadata->faviconUrl;
            }

            if ($normalized !== $resource->normalized_url || $resource->description === '') {
                $attributes['description'] = $metadata->description;
            }

            if ($normalized !== $resource->normalized_url || $resource->preview_image_url === '') {
                $attributes['preview_image_url'] = $metadata->previewImageUrl;
            }
        }

        $resource->update($attributes);
        $resource->collections()->detach();

        if ($request->filled('collection_id')) {
            $collection = $request->user()->collections()->findOrFail($request->string('collection_id')->toString());
            $collection->attachResource($resource);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Link bijgewerkt.']);

        return back();
    }

    public function favorite(Request $request, Resource $resource): RedirectResponse
    {
        abort_unless($resource->user_id === $request->user()->id, 404);

        $resource->update([
            'is_favorite' => ! $resource->is_favorite,
        ]);

        return back();
    }

    public function archive(Request $request, Resource $resource): RedirectResponse
    {
        abort_unless($resource->user_id === $request->user()->id, 404);

        $archived = ! $resource->is_archived;

        $resource->update([
            'is_archived' => $archived,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $archived ? 'Link gearchiveerd.' : 'Link teruggezet.',
        ]);

        return back();
    }

    public function bulk(BulkResourceRequest $request): RedirectResponse
    {
        /** @var list<string> $ids */
        $ids = $request->validated('ids');
        $resources = $request->user()->resources()->whereIn('id', $ids)->get();

        abort_unless($resources->count() === count($ids), 404);

        $action = $request->string('action')->toString();

        match ($action) {
            'favorite' => $this->updateEach($resources, ['is_favorite' => true]),
            'unfavorite' => $this->updateEach($resources, ['is_favorite' => false]),
            'archive' => $this->updateEach($resources, ['is_archived' => true]),
            'restore' => $this->updateEach($resources, ['is_archived' => false]),
            'delete' => $resources->each(fn (Resource $resource) => $resource->delete()),
            default => abort(404),
        };

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $this->bulkMessage($action, $resources->count()),
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

    /**
     * @param  Collection<int, Resource>  $resources
     * @param  array<string, bool>  $attributes
     */
    private function updateEach(Collection $resources, array $attributes): void
    {
        $resources->each(fn (Resource $resource) => $resource->update($attributes));
    }

    private function bulkMessage(string $action, int $count): string
    {
        $many = $count !== 1;

        return match ($action) {
            'favorite' => $many ? 'Links als favoriet gemarkeerd.' : 'Link als favoriet gemarkeerd.',
            'unfavorite' => $many ? 'Favorieten weggehaald.' : 'Favoriet weggehaald.',
            'archive' => $many ? 'Links gearchiveerd.' : 'Link gearchiveerd.',
            'restore' => $many ? 'Links teruggezet.' : 'Link teruggezet.',
            'delete' => $many ? 'Links verwijderd.' : 'Link verwijderd.',
            default => 'Klaar.',
        };
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
