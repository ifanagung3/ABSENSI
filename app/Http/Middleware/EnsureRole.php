<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            throw new AuthenticationException('Silakan masuk terlebih dahulu.');
        }

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Akun tidak aktif.');
        }

        if (! $user->hasRole(...$roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $sessionVersion = $request->session()->get('auth_session_version');
        if ($sessionVersion === null || (int) $sessionVersion !== (int) $user->session_version) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw new AuthenticationException('Sesi telah berakhir. Silakan masuk kembali.');
        }

        if ($user->must_change_password) {
            $allowedRoutes = [
                'admin.profile.edit',
                'admin.profile.update',
                'admin.profile.password',
                'employee.profile.edit',
                'employee.profile.update',
                'employee.profile.password',
            ];

            if (! in_array($request->route()?->getName(), $allowedRoutes, true)) {
                $profileRoute = $user->hasRole('admin')
                    ? 'admin.profile.edit'
                    : 'employee.profile.edit';

                return redirect()->route($profileRoute)->with(
                    'warning',
                    'Untuk keamanan, ganti password sementara sebelum menggunakan fitur lain.'
                );
            }
        }

        return $next($request);
    }
}
