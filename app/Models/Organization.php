<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $type
 * @property string|null $description
 * @property string|null $logo
 * @property bool $verified
 * @property Collection<int, User> $users
 */
final class Organization extends Model
{
    protected $fillable = ['name', 'slug', 'type', 'description', 'logo'];

    /** @return array<string, string> */
    #[\Override]
    protected function casts(): array
    {
        return ['verified' => 'boolean'];
    }

    #[\Override]
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsToMany<User, $this, Pivot, 'pivot'> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function roleFor(User $user): ?Role
    {
        /** @var mixed $role */
        $role = $this->users()->where('users.id', $user->id)->first()?->pivot?->getAttribute('role');

        return is_string($role) ? Role::tryFrom($role) : null;
    }
}
