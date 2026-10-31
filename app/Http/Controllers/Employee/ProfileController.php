<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function edit(Request $request): View
    {
        $employee = $request->user()->employee()->with([
            'department', 'position', 'defaultLocation',
        ])->firstOrFail();

        return view('employee.profile', compact('employee'));
    }

    public function update(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee()->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $old = [
            'name' => $request->user()->name,
            'phone' => $employee->phone,
        ];

        DB::transaction(function () use ($data, $employee, $old, $request): void {
            $request->user()->update(['name' => $data['name']]);
            $employee->update(['phone' => $data['phone'] ?? null]);

            $this->audit->record($request, 'employee.profile_updated', $employee, $old, [
                'name' => $request->user()->fresh()->name,
                'phone' => $employee->fresh()->phone,
            ]);
        });

        return back()->with('success', 'Profil berhasil diperbarui.');
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
            $this->audit->record($request, 'employee.password_changed', $user);
        });

        $request->session()->put('auth_session_version', (int) $user->session_version);

        return back()->with('success', 'Password berhasil diubah.');
    }
}
