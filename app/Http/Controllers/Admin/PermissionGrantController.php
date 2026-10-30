<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PermissionGrantController extends Controller
{
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if ($user->isAdmin()) {
            return back()->withErrors(['permissions' => 'Admin sudah memiliki seluruh izin secara otomatis.']);
        }

        $permissionIds = Permission::query()
            ->whereIn('name', $validated['permissions'] ?? [])
            ->pluck('id');

        $user->permissions()->sync($permissionIds);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'Izin khusus pengurus berhasil diperbarui.');
    }
}
