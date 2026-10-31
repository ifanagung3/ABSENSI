<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function edit(Request $request): View
    {
        return view('admin.profile', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user)],
        ]);
        $old = $user->only(['name', 'email']);
        DB::transaction(function () use ($data, $old, $request, $user): void {
            $user->update($data);
            $this->audit->record($request, 'admin.profile_updated', $user, $old, $user->only(['name', 'email']));
        });

        return back()->with('success', 'Profil administrator berhasil diperbarui.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->max(72)->letters()->numbers()],
        ], [
            'password.max' => 'Password maksimal 72 karakter.',
        ]);

        $user = $request->user();
        DB::transaction(function () use ($data, $request, $user): void {
            $user->forceFill([
                'password' => $data['password'],
                'must_change_password' => false,
                'session_version' => (int) $user->session_version + 1,
            ]);
            $user->setRememberToken(Str::random(60));
            $user->save();
            $this->audit->record($request, 'admin.password_changed', $user);
        });

        $request->session()->put('auth_session_version', (int) $user->session_version);

        return back()->with('success', 'Password administrator berhasil diubah.');
    }
}
