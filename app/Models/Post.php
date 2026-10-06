<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PostType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $user_id
 * @property PostType $type
 * @property string|null $title
 * @property string $text
 * @property string|null $image
 * @property string|null $link
 * @property string|null $link_title
 * @property string|null $link_description
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $hidden_at
 * @property string|null $location
 * @property bool $pinned
 * @property Organization $organization
 * @property User $user
 * @property Collection<int, Category> $categories
 */
final class Post extends Model
{
    protected $fillable = ['type', 'title', 'text', 'image', 'link', 'link_title', 'link_description', 'published_at', 'expires_at', 'starts_at', 'ends_at', 'location', 'pinned'];

    /** @return array<string, string> */
    #[\Override]
    protected function casts(): array
    {
        return ['type' => PostType::class, 'pinned' => 'boolean', 'published_at' => 'datetime', 'expires_at' => 'datetime', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'hidden_at' => 'datetime'];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Category, $this, Pivot, 'pivot'> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNull('hidden_at')->whereNotNull('published_at')->where('published_at', '<=', now())->where(function (Builder $query): void {
            $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}
