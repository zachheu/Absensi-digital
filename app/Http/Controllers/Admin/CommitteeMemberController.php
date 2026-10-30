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
            'user_id' => ['required', 'exists:users,id'],
            'committee_position_id' => ['required', 'exists:committee_positions,id'],
        ]);

        $exists = $activity->committeeMembers()
            ->where('user_id', $validated['user_id'])
            ->where('committee_position_id', $validated['committee_position_id'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['committee' => 'Penugasan yang sama sudah tercatat untuk kegiatan ini.']);
        }

        $activity->committeeMembers()->create($validated);

        return back()->with('status', 'Panitia berhasil ditugaskan.');
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
