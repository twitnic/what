<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return view('dashboard', ['organizations' => $user->organizations()->get(), 'tokens' => $user->tokens()->get()]);
    }

    public function token(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        Validator::make($request->all(), ['name' => ['required', 'string', 'max:100']])->validate();
        $token = $user->createToken($request->string('name')->toString(), ['posts:write'], now()->addDays(90));

        return back()->with('token', $token->plainTextToken);
    }

    public function revoke(Request $request, int $token): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $user->tokens()->whereKey($token)->firstOrFail()->delete();

        return back()->with('status', 'Token widerrufen.');
    }
}
