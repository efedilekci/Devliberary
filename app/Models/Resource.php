<?php

namespace App\Models;

use App\Support\UrlNormalizer;
use Database\Factories\ResourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string $url
 * @property string $normalized_url
 * @property string $normalized_hash
 * @property string $title
 * @property string $description
 * @property string $favicon_url
 * @property string $preview_image_url
 * @property string $category
 * @property string $notes
 * @property list<string> $tags
 * @property bool $is_favorite
 * @property bool $is_archived
 * @property Carbon|null $last_opened_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Resource extends Model
{
    /** @use HasFactory<ResourceFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'url',
        'title',
        'description',
        'favicon_url',
        'preview_image_url',
        'category',
        'notes',
        'tags',
        'is_favorite',
        'is_archived',
        'last_opened_at',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Collection, $this>
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_resource')
            ->withPivot(['user_id', 'position'])
            ->orderByPivot('position');
    }

    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        $query->where(function (Builder $query) use ($like): void {
            $query->whereRaw("title like ? escape '!'", [$like])
                ->orWhereRaw("url like ? escape '!'", [$like])
                ->orWhereRaw("description like ? escape '!'", [$like])
                ->orWhereRaw("tags like ? escape '!'", [$like]);
        });
    }

    protected static function booted(): void
    {
        static::saving(function (Resource $resource): void {
            if ($resource->isDirty('url') || $resource->normalized_url === null) {
                $normalized = UrlNormalizer::normalize($resource->url);
                $resource->normalized_url = $normalized;
                $resource->normalized_hash = UrlNormalizer::hash($normalized);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_favorite' => 'boolean',
            'is_archived' => 'boolean',
            'last_opened_at' => 'datetime',
        ];
    }
}
