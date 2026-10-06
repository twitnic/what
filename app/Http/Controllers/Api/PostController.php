<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostRequest;
use App\Http\Resources\PostResource;
use App\Models\Organization;
use App\Models\Post;
use App\Models\User;
use App\Services\Feed;
use App\Services\PostWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class PostController extends Controller
{
    public function index(Request $request, Feed $feed): AnonymousResourceCollection
    {
        return PostResource::collection($feed->query($request)->paginate(30)->withQueryString());
    }

    public function organization(Request $request, Organization $organization, Feed $feed): AnonymousResourceCollection
    {
        return PostResource::collection($feed->query($request, $organization)->paginate(30)->withQueryString());
    }

    public function show(Post $post): PostResource
    {
        abort_unless(Post::query()->visible()->whereKey($post->id)->exists(), 404);

        return new PostResource($post->load(['organization', 'categories']));
    }

    public function store(PostRequest $request, Organization $organization, PostWriter $writer): PostResource
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return new PostResource($writer->save($request, $organization, $user)->load(['organization', 'categories']));
    }

    public function update(PostRequest $request, Organization $organization, Post $post, PostWriter $writer): PostResource
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return new PostResource($writer->save($request, $organization, $user, $post)->load(['organization', 'categories']));
    }

    public function destroy(Organization $organization, Post $post): Response
    {
        abort_unless($post->organization_id === $organization->id, 404);
        Gate::authorize('delete', $post);
        $image = $post->image;
        $post->delete();
        if ($image !== null) {
            Storage::disk('public')->delete($image);
        }

        return response()->noContent();
    }
}
