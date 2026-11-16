<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'in:created,updated,deleted'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ], [
            'date_to.after_or_equal' => 'La date de fin doit être égale ou postérieure à la date de début.',
        ]);

        $query = ActivityLog::query()->with('user');
        if (! empty($filters['search'])) $query->where(fn ($builder) => $builder->where('description', 'like', '%'.$filters['search'].'%')->orWhere('subject_type', 'like', '%'.$filters['search'].'%'));
        if (! empty($filters['action'])) $query->where('action', $filters['action']);
        if (! empty($filters['user_id'])) $query->where('user_id', $filters['user_id']);
        if (! empty($filters['date_from'])) $query->whereDate('created_at', '>=', $filters['date_from']);
        if (! empty($filters['date_to'])) $query->whereDate('created_at', '<=', $filters['date_to']);

        return view('admin.activity-logs.index', [
            'logs' => $query->latest('id')->paginate(25)->withQueryString(),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
