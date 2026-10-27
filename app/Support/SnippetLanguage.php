<?php

namespace App\Support;

final class SnippetLanguage
{
    /**
     * @var list<string>
     */
    public const VALUES = [
        'typescript',
        'javascript',
        'tsx',
        'jsx',
        'html',
        'css',
        'json',
        'python',
        'bash',
        'sql',
        'text',
        'lua',
    ];

    public static function label(string $language): string
    {
        return match ($language) {
            'typescript' => 'TypeScript',
            'javascript' => 'JavaScript',
            'tsx' => 'TSX',
            'jsx' => 'JSX',
            'html' => 'HTML',
            'css' => 'CSS',
            'json' => 'JSON',
            'python' => 'Python',
            'bash' => 'Bash',
            'sql' => 'SQL',
            'text' => 'Platte tekst',
            'lua' => 'Lua',
            default => $language,
        };
    }
}
