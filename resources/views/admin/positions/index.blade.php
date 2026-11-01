@extends('layouts.app')

@section('title', 'Jabatan Kepanitiaan')
@section('subtitle', 'Dua belas jabatan panitia, terpisah dari bidang')

@section('content')
    <div class="grid gap-4 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="border-b border-cream-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-army-900">Daftar jabatan</h2>
                <p class="mt-1 text-xs text-army-600">Jabatan kepanitiaan tidak memberikan hak akses aplikasi.</p>
            </div>

            <div class="divide-y divide-cream-200">
                @forelse ($positions as $position)
                    <form method="POST" action="{{ route('admin.positions.update', $position) }}" class="grid gap-3 px-4 py-4 sm:grid-cols-12">
                        @csrf
                        @method('PUT')

                        <div class="sm:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-army-600">Kategori</p>
                            <p class="mt-1 text-xs text-army-800">{{ $position->category->label() }}</p>
                            <p class="text-xs text-army-600">{{ $position->committee_members_count }} penugasan</p>
                        </div>

                        <div class="sm:col-span-4">
                            <label for="name-{{ $position->id }}" class="label">Nama jabatan</label>
                            <input id="name-{{ $position->id }}" name="name" value="{{ old('name', $position->name) }}" required class="input">
                            @error('name') <p class="error">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-3">
                            <label for="category-{{ $position->id }}" class="label">Kategori</label>
                            <select id="category-{{ $position->id }}" name="category" class="input">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->value }}" @selected($position->category === $category)>{{ $category->label() }}</option>
                                @endforeach
                            </select>
                            @error('category') <p class="error">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="description-{{ $position->id }}" class="label">Deskripsi</label>
                            <input id="description-{{ $position->id }}" name="description" value="{{ old('description', $position->description) }}" class="input">
                            @error('description') <p class="error">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col justify-between gap-2 sm:col-span-1">
                            <label class="flex items-center gap-2 text-xs text-army-700">
                                <input type="checkbox" name="is_active" value="1" @checked($position->is_active) class="h-4 w-4 rounded border-cream-400 text-army-700">
                                Aktif
                            </label>
                            <button class="btn btn-secondary px-2 py-1 text-xs">Simpan</button>
                        </div>
                    </form>
                @empty
                    <p class="px-4 py-6 text-sm text-army-600">Belum ada jabatan kepanitiaan.</p>
                @endforelse
            </div>
        </section>

        <section class="card h-fit p-4">
            <h2 class="text-sm font-semibold text-army-900">Tambah jabatan</h2>
            <p class="mt-1 text-xs text-army-600">Gunakan hanya bila kegiatan membutuhkan jabatan baru.</p>

            <form method="POST" action="{{ route('admin.positions.store') }}" class="mt-3 space-y-3">
                @csrf

                <div>
                    <label for="name" class="label">Nama jabatan</label>
                    <input id="name" name="name" value="{{ old('name') }}" required class="input" placeholder="Contoh: Divisi Dokumentasi">
                    @error('name') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="category" class="label">Kategori</label>
                    <select id="category" name="category" class="input" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->value }}" @selected(old('category') === $category->value)>{{ $category->label() }}</option>
                        @endforeach
                    </select>
                    @error('category') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="description" class="label">Deskripsi (opsional)</label>
                    <input id="description" name="description" value="{{ old('description') }}" class="input">
                    @error('description') <p class="error">{{ $message }}</p> @enderror
                </div>

                <button class="btn btn-primary w-full">Tambah jabatan</button>
            </form>
        </section>
    </div>
@endsection
