@extends('layouts.app')

@section('title', 'Edit Pengurus')
@section('subtitle', $user->name.' · '.$user->npp)

@section('content')
    <div class="grid gap-4 xl:grid-cols-3">
        <div class="card p-4 sm:p-6 xl:col-span-2">
            @include('admin.users._form')
        </div>

        <section class="card h-fit p-4">
            <h2 class="text-sm font-semibold text-army-900">Izin khusus</h2>
            <p class="mt-1 text-xs text-army-500">Izin ini terpisah dari role dan jabatan kepanitiaan.</p>

            @if ($user->isAdmin())
                <p class="mt-3 rounded-lg bg-cream-100 p-3 text-xs text-army-600">Admin otomatis memiliki seluruh izin.</p>
            @else
                <form method="POST" action="{{ route('admin.users.permissions', $user) }}" class="mt-3 space-y-3">
                    @csrf
                    @method('PUT')

                    @forelse ($permissions as $permission)
                        <label class="flex items-start gap-2 rounded-lg border border-cream-300 p-3 text-sm">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                   @checked($user->permissions->contains('name', $permission->name))
                                   class="mt-0.5 h-4 w-4 rounded border-cream-400 text-army-700">
                            <span>
                                <span class="font-medium text-army-900">{{ $permission->name }}</span>
                                <span class="block text-xs text-army-500">{{ $permission->description }}</span>
                            </span>
                        </label>
                    @empty
                        <p class="text-xs text-army-500">Belum ada izin yang tersedia.</p>
                    @endforelse

                    <button class="btn btn-primary w-full">Simpan izin</button>
                </form>

                <p class="mt-3 text-xs leading-relaxed text-army-500">
                    Pemegang izin attendance.correct hanya dapat mengoreksi absensi pada kegiatan yang diikuti sebagai panitia. Admin dapat mengoreksi semua kegiatan.
                </p>
            @endif
        </section>
    </div>
@endsection
