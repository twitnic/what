<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class Feed
{
    /** @return Builder<Post> */
    public function query(Request $request, ?Organization $organization = null): Builder
    {
        $query = Post::query()->visible()->with(['organization', 'categories']);
        if ($organization !== null) {
            $query->where('organization_id', $organization->id);
        }
        $type = $request->string('type')->toString();
        if ($type !== '') {
            $query->where('type', $type);
        }
        $category = $request->string('category')->toString();
        if ($category !== '') {
            $query->whereHas('categories', function (Builder $query) use ($category): void {
                $query->where('slug', $category);
            });
        }
        $search = mb_substr($request->string('q')->toString(), 0, 100);
        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('text', 'like', '%'.$search.'%')->orWhere('title', 'like', '%'.$search.'%');
            });
        }

        return $query->orderByDesc('pinned')->orderByDesc('published_at')->orderByDesc('id');
    }
}
