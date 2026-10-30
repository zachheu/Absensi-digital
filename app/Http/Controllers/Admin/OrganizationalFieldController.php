<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrganizationalField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationalFieldController extends Controller
{
    public function index(): View
    {
        return view('admin.fields.index', [
            'fields' => OrganizationalField::query()->withCount('users')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:organizational_fields,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'code.unique' => 'Kode bidang sudah digunakan.',
        ]);

        OrganizationalField::query()->create([
            ...$validated,
            'is_active' => true,
        ]);

        return back()->with('status', 'Bidang berhasil ditambahkan.');
    }

    public function update(Request $request, OrganizationalField $organizationalField): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $organizationalField->update($validated);

        return back()->with('status', 'Bidang berhasil diperbarui.');
    }
}
