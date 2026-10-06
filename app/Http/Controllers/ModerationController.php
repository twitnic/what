<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ModerationController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->is_platform_admin, 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('moderation', ['reports' => Report::query()->whereNull('resolved_at')->with('post.organization')->latest()->paginate(20), 'organizations' => Organization::query()->orderBy('name')->get(), 'hiddenPosts' => Post::query()->whereNotNull('hidden_at')->with('organization')->latest()->limit(50)->get()]);
    }

    public function verify(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $organization->verified = ! $organization->verified;
        $organization->save();

        return back()->with('status', 'Verifizierungsstatus geändert.');
    }

    public function hide(Request $request, Post $post): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $post->hidden_at = $post->hidden_at === null ? now() : null;
        $post->save();

        return back()->with('status', 'Sichtbarkeit geändert.');
    }

    public function resolve(Request $request, Report $report): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $report->resolved_at = now();
        $report->save();

        return back()->with('status', 'Meldung abgeschlossen.');
    }
}
