<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/** @property int $id
 * @property string $reason
 * @property Carbon|null $resolved_at
 * @property Post $post
 */
final class Report extends Model
{
    protected $fillable = ['post_id', 'reason'];

    /** @return array<string, string> */
    #[\Override]
    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    /** @return BelongsTo<Post, $this> */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
