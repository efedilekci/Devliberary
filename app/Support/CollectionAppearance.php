<?php

namespace App\Support;

class CollectionAppearance
{
    /**
     * @var list<string>
     */
    public const ICONS = [
        'folder',
        'bookmark',
        'star',
        'heart',
        'code',
        'terminal',
        'book',
        'lightbulb',
        'palette',
        'wrench',
        'globe',
        'layers',
        'box',
        'image',
        'file-text',
        'cpu',
        'database',
        'rocket',
    ];

    /**
     * @var list<string>
     */
    public const COLORS = [
        '#F97316',
        '#EAB308',
        '#22C55E',
        '#14B8A6',
        '#3B82F6',
        '#A855F7',
        '#EC4899',
        '#9CA3AF',
    ];

    public static function icon(string $icon): string
    {
        return in_array($icon, self::ICONS, true) ? $icon : 'folder';
    }

    public static function color(string $color): string
    {
        $color = strtoupper($color);

        foreach (self::COLORS as $allowed) {
            if (strtoupper($allowed) === $color) {
                return $allowed;
            }
        }

        return '#F97316';
    }
}
