<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    public function loginForm(): View
    {
        return view('auth.login');
    }

    public function registerForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        /** @var array<string, mixed> $data */
        $data = Validator::make($request->all(), ['name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email', 'max:254', 'unique:users'], 'password' => ['required', 'confirmed', PasswordRule::min(12)]])->validate();
        $user = User::query()->create($data);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = Validator::make($request->all(), ['email' => ['required', 'email'], 'password' => ['required', 'string']])->validate();
        if (! Auth::attempt($data, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Die Zugangsdaten stimmen nicht überein.']);
        }
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('feed');
    }

    public function forgotForm(): View
    {
        return view('auth.forgot');
    }

    public function forgot(Request $request): RedirectResponse
    {
        $data = Validator::make($request->all(), ['email' => ['required', 'email']])->validate();
        Password::sendResetLink($data);

        return back()->with('status', 'Falls ein Konto existiert, erhältst du eine E-Mail zum Zurücksetzen.');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset', ['token' => $token, 'email' => $request->string('email')->toString()]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = Validator::make($request->all(), ['token' => ['required', 'string'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', PasswordRule::min(12)]])->validate();
        $status = Password::reset($data, function (User $user, string $password): void {
            $user->password = Hash::make($password);
            $user->setRememberToken(Str::random(60));
            $user->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => 'Der Link ist ungültig oder abgelaufen.']);
        }

        return redirect()->route('login')->with('status', 'Dein Passwort wurde geändert.');
    }
}
