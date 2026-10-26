<?php

namespace App\Models;

use Database\Factories\CollectionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * @property string $id
 * @property string $user_id
 * @property string $name
 * @property string $description
 * @property string $icon
 * @property string $accent_color
 * @property int $position
 * @property bool $is_pinned_to_home
 * @property bool $is_collapsed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Collection extends Model
{
    /** @use HasFactory<CollectionFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'icon',
        'accent_color',
        'position',
        'is_pinned_to_home',
        'is_collapsed',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Resource, $this>
     */
    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'collection_resource')
            ->withPivot(['user_id', 'position'])
            ->orderByPivot('position');
    }

    public function attachResource(Resource $resource, int $position = 0): void
    {
        if ($resource->user_id !== $this->user_id) {
            throw new InvalidArgumentException('Een link hoort bij een andere gebruiker.');
        }

        $this->resources()->syncWithoutDetaching([
            $resource->id => [
                'user_id' => $this->user_id,
                'position' => $position,
            ],
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_pinned_to_home' => 'boolean',
            'is_collapsed' => 'boolean',
        ];
    }
}
