<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Organization;
use App\Models\Post;
use App\Models\Report;
use App\Services\Feed;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

final class FeedController extends Controller
{
    public function index(Request $request, Feed $feed): View
    {
        return view('feed', ['posts' => $feed->query($request)->paginate(15)->withQueryString(), 'categories' => Category::all(), 'organization' => null]);
    }

    public function organization(Request $request, Organization $organization, Feed $feed): View
    {
        return view('feed', ['posts' => $feed->query($request, $organization)->paginate(15)->withQueryString(), 'categories' => Category::all(), 'organization' => $organization]);
    }

    public function calendar(): View
    {
        return view('calendar', ['posts' => Post::query()->visible()->where('type', 'event')->where('starts_at', '>=', now()->startOfDay())->with('organization')->orderBy('starts_at')->paginate(30)]);
    }

    public function rss(Request $request, Feed $feed): Response
    {
        $posts = $feed->query($request)->limit(50)->get();

        return response()->view('rss', ['posts' => $posts])->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    public function report(Request $request, Post $post): RedirectResponse
    {
        abort_unless(Post::query()->visible()->whereKey($post->id)->exists(), 404);
        $data = Validator::make($request->all(), ['reason' => ['required', 'string', 'min:10', 'max:1000']])->validate();
        Report::query()->create(['post_id' => $post->id, 'reason' => $data['reason']]);

        return back()->with('status', 'Vielen Dank. Die Meldung wird geprüft.');
    }
}
