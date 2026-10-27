<?php

namespace App\Support;

class TagList
{
    /**
     * @return list<string>
     */
    public static function parse(string $value): array
    {
        $tags = [];

        foreach (self::parts($value) as $part) {
            $key = mb_strtolower($part);

            if (! array_key_exists($key, $tags)) {
                $tags[$key] = $part;
            }
        }

        return array_values($tags);
    }

    public static function problem(string $value): ?string
    {
        $tags = self::parse($value);

        if (count($tags) > 8) {
            return 'Je kunt hoogstens 8 tags toevoegen.';
        }

        foreach ($tags as $tag) {
            if (mb_strlen($tag) > 30) {
                return 'Een tag mag hoogstens 30 tekens zijn.';
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function parts(string $value): array
    {
        $chunks = preg_split('/\s*,\s*/u', trim($value)) ?: [];
        $parts = [];

        foreach ($chunks as $chunk) {
            $tag = preg_replace('/\s+/u', ' ', trim($chunk)) ?? '';
            $tag = ltrim($tag, '#');
            $tag = trim(str_replace("\0", '', $tag));

            if ($tag !== '') {
                $parts[] = $tag;
            }
        }

        return $parts;
    }
}
