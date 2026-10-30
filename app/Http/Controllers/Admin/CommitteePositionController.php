<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommitteePositionCategory;
use App\Http\Controllers\Controller;
use App\Models\CommitteePosition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CommitteePositionController extends Controller
{
    public function index(): View
    {
        return view('admin.positions.index', [
            'positions' => CommitteePosition::query()
                ->withCount('committeeMembers')
                ->orderByRaw("FIELD(category, 'structural', 'division')")
                ->orderBy('id')
                ->get(),
            'categories' => CommitteePositionCategory::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:committee_positions,name'],
            'category' => ['required', Rule::enum(CommitteePositionCategory::class)],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.unique' => 'Nama jabatan sudah ada.',
        ]);

        CommitteePosition::query()->create([
            ...$validated,
            'is_active' => true,
        ]);

        return back()->with('status', 'Jabatan kepanitiaan berhasil ditambahkan.');
    }

    public function update(Request $request, CommitteePosition $committeePosition): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('committee_positions', 'name')->ignore($committeePosition)],
            'category' => ['required', Rule::enum(CommitteePositionCategory::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $committeePosition->update($validated);

        return back()->with('status', 'Jabatan kepanitiaan berhasil diperbarui.');
    }
}
