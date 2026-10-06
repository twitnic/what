<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PostType;
use App\Enums\Role;
use App\Http\Requests\PostRequest;
use App\Models\Organization;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class PostWriter
{
    public function save(PostRequest $request, Organization $organization, User $user, ?Post $post = null): Post
    {
        if ($post === null) {
            Gate::forUser($user)->authorize('createPost', $organization);
        } else {
            abort_unless($post->organization_id === $organization->id, 404);
            Gate::forUser($user)->authorize('update', $post);
        }
        $previousPin = $post === null ? false : $post->pinned;
        $pin = $request->has('pinned') ? $request->boolean('pinned') : $previousPin;
        if ($pin !== $previousPin) {
            abort_unless(in_array($organization->roleFor($user), [Role::Owner, Role::Admin], true), 403);
        }
        /** @var array<string, mixed> $data */
        $data = $request->safe()->except(['image', 'categories']);
        $data['pinned'] = $pin;
        foreach (['published_at', 'expires_at', 'starts_at', 'ends_at'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = Carbon::parse($data[$field])->utc()->toDateTimeString();
            }
        }
        if (($data['type'] ?? '') !== PostType::Event->value) {
            $data['starts_at'] = null;
            $data['ends_at'] = null;
            $data['location'] = null;
        }
        $file = $request->file('image');
        $oldImage = $post?->image;
        $newImage = $file instanceof UploadedFile ? $file->store('posts', 'public') : null;
        if ($newImage === false) {
            throw new \RuntimeException('Das Bild konnte nicht gespeichert werden.');
        }
        if ($newImage !== null) {
            $data['image'] = $newImage;
        }
        try {
            $saved = DB::transaction(function () use ($post, $organization, $user, $request, $data): Post {
                $saved = $post ?? new Post;
                $saved->fill($data);
                if ($post === null) {
                    $saved->organization()->associate($organization);
                    $saved->user()->associate($user);
                }
                $saved->save();
                /** @var list<int|numeric-string> $categories */
                $categories = $request->validated('categories', []);
                $ids = array_map(static fn (int|string $id): int => (int) $id, $categories);
                $saved->categories()->sync($ids);

                return $saved;
            });
        } catch (\Throwable $exception) {
            if ($newImage !== null) {
                Storage::disk('public')->delete($newImage);
            }
            throw $exception;
        }
        if ($file instanceof UploadedFile && $oldImage !== null) {
            Storage::disk('public')->delete($oldImage);
        }

        return $saved;
    }
}
