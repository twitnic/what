<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class OrganizationController extends Controller
{
    public function create(): View
    {
        return view('organizations.form', ['organization' => new Organization]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $data = $this->validated($request);
        $file = $request->file('logo');
        if ($file instanceof UploadedFile) {
            $data['logo'] = $file->store('logos', 'public');
        }
        $organization = DB::transaction(function () use ($data, $user): Organization {
            $organization = Organization::query()->create($data);
            $organization->users()->attach($user->id, ['role' => Role::Owner->value]);

            return $organization;
        });

        return redirect()->route('organizations.edit', $organization)->with('status', 'Organisation angelegt.');
    }

    public function edit(Organization $organization): View
    {
        Gate::authorize('view', $organization);

        return view('organizations.form', ['organization' => $organization->load('users')]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        Gate::authorize('update', $organization);
        $data = $this->validated($request, $organization);
        $file = $request->file('logo');
        if ($file instanceof UploadedFile) {
            $data['logo'] = $file->store('logos', 'public');
        }
        $oldLogo = $organization->logo;
        $organization->update($data);
        if ($file instanceof UploadedFile && $oldLogo !== null) {
            Storage::disk('public')->delete($oldLogo);
        }

        return redirect()->route('organizations.edit', $organization)->with('status', 'Organisation gespeichert.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Organization $organization = null): array
    {
        /** @var array<string, mixed> $data */
        $data = Validator::make($request->all(), ['name' => ['required', 'string', 'max:120'], 'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('organizations')->ignore($organization?->id)], 'type' => ['required', Rule::in(['club', 'business', 'city', 'other'])], 'description' => ['nullable', 'string', 'max:1000'], 'logo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048']])->validate();
        unset($data['logo']);

        return $data;
    }

    public function member(Request $request, Organization $organization): RedirectResponse
    {
        Gate::authorize('manageMembers', $organization);
        Validator::make($request->all(), ['email' => ['required', 'email', 'exists:users,email'], 'role' => ['required', Rule::in([Role::Admin->value, Role::Editor->value])]])->validate();
        $user = User::query()->where('email', $request->string('email')->toString())->firstOrFail();
        abort_if($organization->roleFor($user) === Role::Owner, 422, 'Die Owner-Rolle kann hier nicht geändert werden.');
        $organization->users()->syncWithoutDetaching([$user->id => ['role' => $request->string('role')->toString()]]);

        return back()->with('status', 'Mitglied gespeichert.');
    }

    public function removeMember(Organization $organization, User $user): RedirectResponse
    {
        Gate::authorize('manageMembers', $organization);
        abort_if($organization->roleFor($user) === Role::Owner, 422);
        $organization->users()->detach($user->id);

        return back()->with('status', 'Mitglied entfernt.');
    }
}
