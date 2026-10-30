@extends('layouts.app')

@section('title', 'Bidang Organisasi')
@section('subtitle', 'Kelola empat bidang HIMATIF')

@section('content')
    <div class="grid gap-4 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="border-b border-cream-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-army-900">Daftar bidang</h2>
                <p class="mt-1 text-xs text-army-500">Bidang bersifat tetap: HRDO, RE, PR, dan CNB.</p>
            </div>

            <div class="divide-y divide-cream-200">
                @forelse ($fields as $field)
                    <form method="POST" action="{{ route('admin.fields.update', $field) }}" class="grid gap-3 px-4 py-4 sm:grid-cols-12">
                        @csrf
                        @method('PUT')

                        <div class="sm:col-span-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-army-500">Kode</p>
                            <p class="mt-1 text-sm font-medium text-army-900">{{ $field->code }}</p>
                            <p class="text-xs text-army-500">{{ $field->users_count }} pengurus</p>
                        </div>

                        <div class="sm:col-span-5">
                            <label for="name-{{ $field->id }}" class="label">Nama tampilan</label>
                            <input id="name-{{ $field->id }}" name="name" value="{{ old('name', $field->name) }}" required class="input">
                            @error('name') <p class="error">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-3">
                            <label for="description-{{ $field->id }}" class="label">Deskripsi</label>
                            <input id="description-{{ $field->id }}" name="description" value="{{ old('description', $field->description) }}" class="input">
                            @error('description') <p class="error">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col justify-between gap-2 sm:col-span-1">
                            <label class="flex items-center gap-2 text-xs text-army-700">
                                <input type="checkbox" name="is_active" value="1" @checked($field->is_active) class="h-4 w-4 rounded border-cream-400 text-army-700">
                                Aktif
                            </label>
                            <button class="btn btn-secondary px-2 py-1 text-xs">Simpan</button>
                        </div>
                    </form>
                @empty
                    <p class="px-4 py-6 text-sm text-army-500">Belum ada bidang. Jalankan seeder bidang terlebih dahulu.</p>
                @endforelse
            </div>
        </section>

        <section class="card h-fit p-4">
            <h2 class="text-sm font-semibold text-army-900">Tambah bidang</h2>
            <p class="mt-1 text-xs text-army-500">Hanya digunakan bila struktur organisasi berubah.</p>

            <form method="POST" action="{{ route('admin.fields.store') }}" class="mt-3 space-y-3">
                @csrf

                <div>
                    <label for="code" class="label">Kode</label>
                    <input id="code" name="code" value="{{ old('code') }}" required maxlength="20" class="input uppercase" placeholder="Contoh: HRDO">
                    @error('code') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="name" class="label">Nama tampilan</label>
                    <input id="name" name="name" value="{{ old('name') }}" required class="input">
                    @error('name') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="description" class="label">Deskripsi (opsional)</label>
                    <input id="description" name="description" value="{{ old('description') }}" class="input">
                    @error('description') <p class="error">{{ $message }}</p> @enderror
                </div>

                <button class="btn btn-primary w-full">Tambah bidang</button>
            </form>
        </section>
    </div>
@endsection
