<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\Post;
use App\Models\User;

final class PostPolicy
{
    public function update(User $user, Post $post): bool
    {
        $role = $post->organization->roleFor($user);

        return $role === Role::Owner || $role === Role::Admin || ($role === Role::Editor && $post->user_id === $user->id);
    }

    public function delete(User $user, Post $post): bool
    {
        return $this->update($user, $post);
    }
}
