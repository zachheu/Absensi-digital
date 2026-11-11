<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityStatus;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\CommitteeMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommitteeMemberController extends Controller
{
    public function store(Request $request, Activity $activity): RedirectResponse
    {
        if ($activity->recap_finalized_at !== null) {
            return back()->withErrors(['committee' => 'Rekap kegiatan sudah difinalisasi. Susunan panitia tidak dapat diubah.']);
        }

        $validated = $request->validate([
            'committee_position_id' => ['required', 'exists:committee_positions,id'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'head_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $headUserId = $validated['head_user_id'] ?? null;

        if ($headUserId !== null && ! in_array((int) $headUserId, array_map('intval', $validated['user_ids']), true)) {
            return back()->withErrors(['committee' => 'Kepala Divisi harus termasuk dalam daftar pengurus yang dipilih.'])->withInput();
        }

        $created = 0;
        $skipped = 0;

        foreach ($validated['user_ids'] as $userId) {
            $exists = $activity->committeeMembers()
                ->where('user_id', $userId)
                ->where('committee_position_id', $validated['committee_position_id'])
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            $activity->committeeMembers()->create([
                'user_id' => $userId,
                'committee_position_id' => $validated['committee_position_id'],
                'is_head' => (int) $userId === (int) ($headUserId ?? 0),
            ]);
            $created++;
        }

        // Pastikan flag kepala konsisten untuk jabatan ini.
        $activity->committeeMembers()
            ->where('committee_position_id', $validated['committee_position_id'])
            ->update(['is_head' => false]);

        if ($headUserId !== null) {
            $activity->committeeMembers()
                ->where('committee_position_id', $validated['committee_position_id'])
                ->where('user_id', $headUserId)
                ->update(['is_head' => true]);
        }

        $message = $created.' pengurus ditugaskan.';
        if ($skipped > 0) {
            $message .= ' '.$skipped.' sudah tercatat sebelumnya dan dilewati.';
        }

        return back()->with('status', $message);
    }

    public function update(Request $request, Activity $activity): RedirectResponse
    {
        if ($activity->recap_finalized_at !== null) {
            return back()->withErrors(['committee' => 'Rekap kegiatan sudah difinalisasi. Susunan panitia tidak dapat diubah.']);
        }

        if ($activity->status === ActivityStatus::Completed) {
            return back()->withErrors(['committee' => 'Kegiatan sudah selesai. Susunan panitia tidak dapat diubah.']);
        }

        $validated = $request->validate([
            'committee_member_id' => ['required', 'integer', 'exists:committee_members,id'],
            'committee_position_id' => ['required', 'integer', 'exists:committee_positions,id'],
        ]);

        $committeeMember = $activity->committeeMembers()->findOrFail($validated['committee_member_id']);

        $duplicate = $activity->committeeMembers()
            ->where('user_id', $committeeMember->user_id)
            ->where('committee_position_id', $validated['committee_position_id'])
            ->whereKeyNot($committeeMember->getKey())
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['committee' => 'Pengurus ini sudah memiliki jabatan tersebut pada kegiatan ini.']);
        }

        $committeeMember->update(['committee_position_id' => $validated['committee_position_id']]);

        return back()->with('status', 'Jabatan '.$committeeMember->user->name.' dipindahkan ke '.$committeeMember->position()->value('name').'.');
    }

    public function updateHead(Request $request, Activity $activity): RedirectResponse
    {
        if ($activity->recap_finalized_at !== null) {
            return back()->withErrors(['committee' => 'Rekap kegiatan sudah difinalisasi. Susunan panitia tidak dapat diubah.']);
        }

        if ($activity->status === ActivityStatus::Completed) {
            return back()->withErrors(['committee' => 'Kegiatan sudah selesai. Susunan panitia tidak dapat diubah.']);
        }

        $validated = $request->validate([
            'committee_position_id' => ['required', 'integer', 'exists:committee_positions,id'],
            'head_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $positionId = (int) $validated['committee_position_id'];
        $headUserId = $validated['head_user_id'] ?? null;

        // Hanya anggota yang sudah tercatat pada jabatan ini yang boleh jadi Kadiv.
        if ($headUserId !== null) {
            $isMember = $activity->committeeMembers()
                ->where('committee_position_id', $positionId)
                ->where('user_id', $headUserId)
                ->exists();

            if (! $isMember) {
                return back()->withErrors(['committee' => 'Kepala Divisi harus salah satu anggota jabatan tersebut.'])->withInput();
            }
        }

        $activity->committeeMembers()
            ->where('committee_position_id', $positionId)
            ->update(['is_head' => false]);

        if ($headUserId !== null) {
            $activity->committeeMembers()
                ->where('committee_position_id', $positionId)
                ->where('user_id', $headUserId)
                ->update(['is_head' => true]);
        }

        return back()->with('status', 'Kepala Divisi diperbarui.');
    }

    public function destroy(Activity $activity, CommitteeMember $committeeMember): RedirectResponse
    {
        if ($committeeMember->activity_id !== $activity->id) {
            abort(404);
        }

        if ($committeeMember->user->attendances()->where('activity_id', $activity->id)->exists()) {
            return back()->withErrors(['committee' => 'Panitia ini sudah memiliki absensi. Penugasan tidak dapat dihapus agar riwayat tetap utuh.']);
        }

        if ($activity->recap_finalized_at !== null || $activity->status === ActivityStatus::Completed) {
            return back()->withErrors(['committee' => 'Kegiatan sudah selesai. Susunan panitia tidak dapat diubah.']);
        }

        $committeeMember->delete();

        return back()->with('status', 'Penugasan panitia dihapus.');
    }
}
