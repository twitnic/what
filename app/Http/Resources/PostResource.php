<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

final class PostResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[\Override]
    public function toArray(Request $request): array
    {
        $post = $this->resource;
        assert($post instanceof Post);

        return ['id' => $post->id, 'type' => $post->type->value, 'title' => $post->title, 'text' => $post->text, 'image_url' => $post->image === null ? null : Storage::disk('public')->url($post->image), 'link' => $post->link, 'link_title' => $post->link_title, 'link_description' => $post->link_description, 'pinned' => $post->pinned, 'published_at' => $post->published_at?->toIso8601String(), 'expires_at' => $post->expires_at?->toIso8601String(), 'event' => $post->starts_at === null ? null : ['starts_at' => $post->starts_at->toIso8601String(), 'ends_at' => $post->ends_at?->toIso8601String(), 'location' => $post->location], 'organization' => ['id' => $post->organization->id, 'name' => $post->organization->name, 'slug' => $post->organization->slug, 'verified' => $post->organization->verified], 'categories' => $post->categories->map(fn (Category $category): array => ['id' => $category->id, 'name' => $category->name, 'slug' => $category->slug])->all()];
    }
}
