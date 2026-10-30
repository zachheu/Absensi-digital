<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StructuralPosition;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\OrganizationalField;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('organizationalField')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.(string) $request->string('search').'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('npp', 'like', $search)
                        ->orWhere('npm', 'like', $search);
                });
            })
            ->when($request->filled('field'), fn ($query) => $query->where('organizational_field_id', $request->integer('field')))
            ->when($request->filled('role'), fn ($query) => $query->where('role', (string) $request->string('role')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'fields' => OrganizationalField::query()->orderBy('code')->get(),
            'roles' => UserRole::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'user' => new User(['role' => UserRole::Pengurus, 'is_active' => true]),
            'fields' => OrganizationalField::query()->where('is_active', true)->orderBy('code')->get(),
            'roles' => UserRole::cases(),
            'structuralPositions' => StructuralPosition::cases(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $user = User::query()->create($data);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Akun pengurus '.$user->name.' berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->load('permissions'),
            'fields' => OrganizationalField::query()->orderBy('code')->get(),
            'roles' => UserRole::cases(),
            'structuralPositions' => StructuralPosition::cases(),
            'permissions' => Permission::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');

        if ($user->is($request->user()) && ! $data['is_active']) {
            return back()->withErrors(['is_active' => 'Kamu tidak dapat menonaktifkan akun sendiri.']);
        }

        if ($user->is($request->user()) && $data['role'] !== UserRole::Admin->value) {
            return back()->withErrors(['role' => 'Kamu tidak dapat menurunkan role akun sendiri.']);
        }

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Data pengurus berhasil diperbarui.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['status' => 'Kamu tidak dapat mengubah status akun sendiri.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('status', 'Status akun '.$user->name.' diubah menjadi '.($user->is_active ? 'aktif' : 'nonaktif').'.');
    }
}
