<?php

namespace App\Models;

use Database\Factories\SnippetFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string $title
 * @property string $code
 * @property string $language
 * @property string $description
 * @property list<string> $tags
 * @property bool $is_favorite
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Snippet extends Model
{
    /** @use HasFactory<SnippetFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'code',
        'language',
        'description',
        'tags',
        'is_favorite',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_favorite' => 'boolean',
        ];
    }
}
