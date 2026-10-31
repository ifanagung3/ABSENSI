<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $logs = AuditLog::with('user')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(function ($sub) use ($term): void {
                    $sub->where('action', 'like', $term)
                        ->orWhere('entity_type', 'like', $term)
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $term));
                });
            })
            ->when(isset($filters['date']), fn ($query) => $query->whereDate('created_at', $filters['date']))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.audit.index', compact('logs'));
    }
}
