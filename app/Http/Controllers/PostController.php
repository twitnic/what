<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Post;
use App\Models\User;
use App\Services\PostWriter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class PostController extends Controller
{
    public function index(Organization $organization): View
    {
        Gate::authorize('view', $organization);

        return view('posts.index', ['organization' => $organization, 'posts' => $organization->posts()->with('user')->latest()->paginate(20)]);
    }

    public function create(Organization $organization): View
    {
        Gate::authorize('createPost', $organization);

        return view('posts.form', ['organization' => $organization, 'post' => new Post, 'categories' => Category::all()]);
    }

    public function edit(Organization $organization, Post $post): View
    {
        abort_unless($post->organization_id === $organization->id, 404);
        Gate::authorize('update', $post);

        return view('posts.form', ['organization' => $organization, 'post' => $post, 'categories' => Category::all()]);
    }

    public function store(PostRequest $request, Organization $organization, PostWriter $writer): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $writer->save($request, $organization, $user);

        return redirect()->route('posts.index', $organization)->with('status', 'Beitrag gespeichert.');
    }

    public function update(PostRequest $request, Organization $organization, Post $post, PostWriter $writer): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $writer->save($request, $organization, $user, $post);

        return redirect()->route('posts.index', $organization)->with('status', 'Beitrag gespeichert.');
    }

    public function destroy(Organization $organization, Post $post): RedirectResponse
    {
        abort_unless($post->organization_id === $organization->id, 404);
        Gate::authorize('delete', $post);
        $image = $post->image;
        $post->delete();
        if ($image !== null) {
            Storage::disk('public')->delete($image);
        }

        return back()->with('status', 'Beitrag gelöscht.');
    }
}
