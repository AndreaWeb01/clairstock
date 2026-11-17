<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.users.index', ['users' => User::with('role')->orderBy('name')->paginate(15)]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', ['roles' => Role::orderBy('label')->get()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('admin.users.index')->with('status', 'Utilisateur créé avec succès.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if ($request->user()->is($user)) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        if ($user->isAdmin() && $user->is_active && User::query()->where('role_id', $user->role_id)->where('is_active', true)->count() <= 1) {
            return back()->with('error', 'Le dernier administrateur actif ne peut pas être désactivé.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return redirect()->route('admin.users.index')->with('status', $user->is_active ? 'Compte activé.' : 'Compte désactivé.');
    }
}
