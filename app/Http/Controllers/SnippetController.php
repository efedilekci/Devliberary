<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSnippetRequest;
use App\Models\Snippet;
use App\Support\SnippetLanguage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SnippetController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('snippets/index', [
            'snippets' => $request->user()->snippets()
                ->latest()
                ->limit(100)
                ->get(['id', 'title', 'code', 'language', 'is_favorite', 'created_at']),
            'languages' => collect(SnippetLanguage::VALUES)->map(fn (string $language) => [
                'value' => $language,
                'label' => SnippetLanguage::label($language),
            ])->values(),
        ]);
    }

    public function store(StoreSnippetRequest $request): RedirectResponse
    {
        $request->user()->snippets()->create([
            'title' => trim($request->string('title')->toString()),
            'code' => $request->string('code')->toString(),
            'language' => $request->string('language')->toString(),
            'description' => '',
            'tags' => [],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Snippet opgeslagen.']);

        return to_route('snippets.index');
    }

    public function favorite(Request $request, Snippet $snippet): RedirectResponse
    {
        abort_unless($snippet->user_id === $request->user()->id, 404);

        $snippet->update([
            'is_favorite' => ! $snippet->is_favorite,
        ]);

        return back();
    }

    public function destroy(Request $request, Snippet $snippet): RedirectResponse
    {
        abort_unless($snippet->user_id === $request->user()->id, 404);

        $snippet->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Snippet verwijderd.']);

        return back();
    }
}
