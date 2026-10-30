@extends('layouts.app')

@section('title', 'Edit Kegiatan')
@section('subtitle', $activity->name)

@section('content')
    <div class="card max-w-3xl p-4 sm:p-6">
        @if ($activity->attendances()->exists())
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Kegiatan ini sudah memiliki absensi. Waktu mulai dan toleransi keterlambatan dikunci agar riwayat tidak berubah diam-diam.
            </div>
        @endif

        @include('activities._form')
    </div>
@endsection
