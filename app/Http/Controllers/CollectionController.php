<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollectionRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CollectionController extends Controller
{
    public function store(StoreCollectionRequest $request): RedirectResponse
    {
        $user = $request->user();
        $position = (int) $user->collections()->max('position') + 1;

        $user->collections()->create([
            'name' => $request->string('name')->toString(),
            'description' => '',
            'position' => $position,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Collectie aangemaakt.']);

        return to_route('dashboard');
    }
}
