<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;

final class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $organization->roleFor($user) !== null;
    }

    public function update(User $user, Organization $organization): bool
    {
        return in_array($organization->roleFor($user), [Role::Owner, Role::Admin], true);
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return $organization->roleFor($user) === Role::Owner;
    }

    public function createPost(User $user, Organization $organization): bool
    {
        return $this->view($user, $organization);
    }
}
