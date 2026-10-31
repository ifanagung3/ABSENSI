<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'max:72'],
        ], [
            'password.max' => 'Password maksimal 72 karakter.',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();
        $user = $request->user();

        if (! $user->is_active) {
            $this->logoutFailedAttempt($request);
            throw ValidationException::withMessages([
                'email' => 'Akun sedang dinonaktifkan.',
            ]);
        }

        $destinationRoute = match ($user->role) {
            'admin' => 'admin.dashboard',
            'employee' => 'employee.dashboard',
            default => null,
        };

        if ($destinationRoute === null) {
            $this->logoutFailedAttempt($request);
            throw ValidationException::withMessages([
                'email' => 'Role akun tidak dikenali. Hubungi administrator.',
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $request->session()->put('auth_session_version', (int) $user->session_version);
        $this->audit->record($request, 'auth.login', $user, null, ['role' => $user->role]);

        if ($user->must_change_password) {
            $destinationRoute = $user->hasRole('admin')
                ? 'admin.profile.edit'
                : 'employee.profile.edit';
        }

        return redirect()->route($destinationRoute);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->audit->record($request, 'auth.logout', $request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah keluar dari sistem.');
    }

    private function logoutFailedAttempt(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
