<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class LinkOrder
{
    public static function forList(string $requested, bool $recent): string
    {
        $allowed = $recent
            ? ['opened', 'newest', 'oldest', 'title']
            : ['newest', 'oldest', 'title'];

        if (! in_array($requested, $allowed, true)) {
            return $recent ? 'opened' : 'newest';
        }

        return $requested;
    }

    public static function forCollection(string $requested): string
    {
        if (! in_array($requested, ['newest', 'oldest', 'title'], true)) {
            return 'position';
        }

        return $requested;
    }

    public static function apply(Builder $query, string $sort, string $table = ''): void
    {
        $prefix = $table === '' ? '' : $table.'.';

        match ($sort) {
            'oldest' => $query->orderBy($prefix.'created_at')->orderBy($prefix.'title'),
            'title' => $query->orderBy($prefix.'title')->orderBy($prefix.'created_at'),
            'opened' => $query->orderByDesc($prefix.'last_opened_at')->orderBy($prefix.'title'),
            default => $query->orderByDesc($prefix.'created_at')->orderBy($prefix.'title'),
        };
    }
}
